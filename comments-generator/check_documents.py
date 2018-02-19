# -*- coding: utf-8 -*-

import sys, requests

from const import LODEPART_BASE_URI, LANGS, LODEPART_LOAD_DOC_URL
from uri_builders import build_doc_part_uri
from sparql_utils import virtuoso_read


LANG_CODE2= {}
for lng in LANGS:
    LANG_CODE2[lng["code3"]] = lng["code2"]


def check_documents():
    found_docs = {}
    # Find all documents defined in the repository
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT DISTINCT ?doc_uri
WHERE {
  ?doc_uri a sioc:Forum .
  FILTER(
    NOT EXISTS {
      ?doc_uri sioc:has_parent ?parent .
    }
  )
}"""
    result = virtuoso_read(req)
    sys.stdout.write(u"\nFound {0:d} document(s) in repository\n"
                     u"".format(len(result)))
    for res_line in result :
        # Search language versions of each document
        doc_uri = res_line[u"doc_uri"]
        sys.stdout.write(u"\n** Document {0}\n".format(doc_uri))
        found_docs[doc_uri] = []
        if not(doc_uri.startswith(LODEPART_BASE_URI)):
            sys.stdout.write(
                u"   ---> Document URI doesn't start with the base URI defined "
                u"in the `const.py` file\n        <{0}> vs. <{1}>\n"
                u"        PLEASE CHECK THIS IS CORRECT!\n"
                u"".format(doc_uri, LODEPART_BASE_URI))
        if LODEPART_BASE_URI.endswith(u"/"):
            sys.stdout.write(
                u"   ---> Base URI defined in the `const.py` file ends with "
                u"\"/\"\n        <{0}>\n        PLEASE CHECK THIS IS CORRECT!\n"
                u"".format(LODEPART_BASE_URI))
        for lang_code3, lang_code2 in LANG_CODE2.items():
            sys.stdout.write(u"\n   {0} version:\n".format(lang_code2.upper()))
            # Get document content path from repository
            lng_doc_uri = build_doc_part_uri(doc_uri, lang_code3)
            req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT DISTINCT ?doc_space
WHERE {
  <%(uri)s> sioc:has_space ?doc_space .
}""" % {u"uri": lng_doc_uri, }
            result = virtuoso_read(req)
            if len(result) == 0:
                sys.stdout.write(
                    u"      ---> Document <{0}> not found in repository\n"
                    u"           SKIPPING DOCUMENT!\n".format(lng_doc_uri))
            elif len(result) > 1:
                sys.stdout.write(
                    u"      ---> Multiple documents <{0}> found in repository\n"
                    u"           REPOSITORY IS INCONSISTENT! SKIPPING DOCUMENT!"
                    u"\n".format(lng_doc_uri))
            else:
                sys.stdout.write(
                    u"      Document <{0}> found in repository; trying to load "
                    u"document content from Web application\n"
                    u"".format(lng_doc_uri))
                # Get actual document content from application
                resp = requests.get(LODEPART_LOAD_DOC_URL,
                                    params={u"path": result[0][u"doc_space"]})
                try:
                    content = resp.json()
                except ValueError:
                    content = {}
                if len(content) > 0:
                    lng_doc_id = content[u"uri"]
                    if not lng_doc_uri.endswith(lng_doc_id):
                        sys.stdout.write(
                            u"      ---> Document ID read from content is "
                            u"inconsistent with document URI\n"
                            u"           \"{0}\" vs <{1}>\n"
                            u"           SKIPPING DOCUMENT!\n"
                            u"".format(lng_doc_id, lng_doc_uri))
                    else:
                        sys.stdout.write(
                            u"      Document content properly retrieved from "
                            u"Web application\n   Ok!\n")
                        found_docs[doc_uri].append(lang_code2)
                else:
                    sys.stdout.write(
                        u"      ---> No content found in Web application for "
                        u"document \"{0}\"\n           SKIPPING DOCUMENT!\n"
                        u"".format(result[0][u"doc_space"]))
    # Write a synthesis
    sys.stdout.write(u"\nSynthesis\n=========\n\n")
    doc_written = False
    for doc_uri, versions in sorted(found_docs.items()):
        if len(versions) == 0:
            continue
        doc_written = True
        sys.stdout.write(u"* {0}\n".format(doc_uri))
        sys.stdout.write(
            u"  versions: {0}\n"
            u"".format(u", ".join([lng.upper() for lng in sorted(versions)])))
    if not doc_written:
        sys.stdout.write(u"No document found\n")
    sys.stdout.write(u"\n")


if __name__ == "__main__":
    check_documents()
