# -*- coding: utf-8 -*-

import requests, json, hashlib, os, sys
from os import path as osp
from datetime import datetime

from const import (LODEPART_LOAD_DOC_URL, DATA_DIR, USERS_FILENAME, LANGS,
                   DEFAULT_DOC_CREATION_DATE)
from uri_builders import build_group_uri, extract_user_id, build_doc_part_uri
from sparql_utils import virtuoso_read


LANG_CODE2= {}
for lng in LANGS:
    LANG_CODE2[lng["code3"]] = lng["code2"]



def load_personae():
    """
    Return a dictionary that gives for each play a dictionary containing the
    personae data. This data contains the corresponding user URI stored inside
    the repository.
    """
    personae = {}
    in_data = {}
    with open(osp.join(DATA_DIR, USERS_FILENAME)) as stream:
        in_data = json.load(stream)
    for user in in_data.values():
        pers_data = {}
        for key in (u"play_id", u"user_id", u"persona", u"email", u"password",
                    u"group", u"lang"):
            pers_data[key] = user[key]
        personae.setdefault(pers_data[u"play_id"], {})
        personae[pers_data[u"play_id"]][pers_data[u"persona"]] = pers_data
        # Get user URI from repository
        md5_email = hashlib.md5(pers_data[u"email"].strip().encode(u"ascii")).hexdigest()
        req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
SELECT DISTINCT ?user_uri
WHERE {
  ?user_uri a sioc:UserAccount ;
            foaf:mbox <%(email)s> ;
            sioc:member_of <%(group)s> .
}""" % {u"email": md5_email, u"group": build_group_uri(pers_data[u"group"]) }
        result = virtuoso_read(req)
        if len(result) != 1:
            raise ValueError(
                u"User {0} ({1}) can't be found in the repository. Have you "
                u"created the users?"
                u"".format(pers_data[u"persona"], pers_data[u"user_id"]))
        pers_data[u"user_uri"] = result[0][u"user_uri"]
        pers_data[u"lodepart_user_id"] = extract_user_id(pers_data[u"user_uri"])
    return personae

def load_plays():
    plays = {}
    for filename in os.listdir(DATA_DIR):
        if filename.startswith(u"_") or osp.splitext(filename)[1] != u".json":
            continue
        with open(osp.join(DATA_DIR, filename)) as stream:
            in_data = json.load(stream)
            in_data[u"lines_min"] = 0
            in_data[u"lines_max"] = len(in_data[u"lines"]) - 1
            in_data[u"lines_cursor"] = 0
            plays.setdefault(in_data[u"lang"], {})
            plays[in_data[u"lang"]][in_data[u"play_id"]] = in_data
    return plays

def build_doc_structure(content, base_uri, accept_comments=True):
    struct = {u"id": content[u"uri"],
              u"uri": build_doc_part_uri(base_uri, content[u"uri"]),
              u"has_content": (content.get(u"content", u"") != u""),
              u"accept_comments": accept_comments, }
    if len(content.get(u"preamble", {})) > 0:
        struct.setdefault(u"children", [])
        struct[u"children"].append(
            build_doc_structure(content[u"preamble"],  base_uri,
                                accept_comments=False) )
    for sect_content in content.get(u"sections", []):
        struct.setdefault(u"children", [])
        struct[u"children"].append(
            build_doc_structure(sect_content,  base_uri) )
    return struct

def load_documents(doc_base_uri,
                   default_creation_date=DEFAULT_DOC_CREATION_DATE):
    """
    Return a dictionary that gives for each language the tree structure of
    the document with the identifiers of each part of this structure (document,
    section, chapter, articles).
    """
    docs = {}
    for lang_code3, lang_code2 in LANG_CODE2.items():
        sys.stdout.write(u"   {0} ".format(lang_code2.upper()))
        sys.stdout.flush()
        # Get document content path from repository
        lng_doc_uri = build_doc_part_uri(doc_base_uri, lang_code3)
        req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
SELECT DISTINCT ?doc_space
       ?doc_date
WHERE {
  <%(uri)s> sioc:has_space ?doc_space .
  OPTIONAL {
    <%(uri)s> lodep:created_at ?doc_date .
  }
}""" % {u"uri": lng_doc_uri, }
        result = virtuoso_read(req)
        if len(result) == 1:
            # Get actual document content from application
            resp = requests.get(LODEPART_LOAD_DOC_URL,
                                params={u"path": result[0][u"doc_space"]})
            content = resp.json()
            if len(content) > 0:
                lng_doc_id = content[u"uri"]
                if not lng_doc_uri.endswith(lng_doc_id):
                    sys.stdout.write(u"error\n\n")
                    raise ValueError(
                        u"Inconsistent document content retrieved from "
                        u"application")
                base_uri = lng_doc_uri[:-(len(lng_doc_id)+1)]
                docs[lang_code2] = build_doc_structure(content, base_uri)
                if u"doc_date" in result[0]:
                    docs[lang_code2][u"creation_date"] = \
                                    datetime.strptime(result[0][u"doc_date"],
                                                      u"%Y-%m-%dT%H:%M:%S")
                else:
                    str_d = default_creation_date.strftime(u"%Y-%m-%d %H:%M:%S")
                    sys.stdout.write(
                        u"No creation date in repository, using default one "
                        u"({0})\n      ".format(str_d))
                    docs[lang_code2][u"creation_date"] = default_creation_date
                sys.stdout.write(u"ok\n")
            else:
                sys.stdout.write(u"no content retrieved from application\n")
        else:
            sys.stdout.write(u"not found in repository\n")
    return docs
