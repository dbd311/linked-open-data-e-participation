# -*- coding: utf-8 -*-

import sys
from xml.sax.saxutils import escape

from const import LANGS, POS_OPINION, NEG_OPINION, MIX_OPINION, LODEPART_GRAPH
from sparql_utils import virtuoso_read, save_items_in_repository
from uri_builders import build_comment_uri


LANG_CODE3 = {}
for lng in LANGS:
    LANG_CODE3[lng[u"code2"]] = lng[u"code3"]
COMMENT_NOTE = {
    POS_OPINION: u"yes",
    NEG_OPINION: u"no",
    MIX_OPINION: u"mixed", }


def save_comments(comments):
    save_items_in_repository(comments, u"comments and replies",
                             build_comment_creation_request)


def build_comment_creation_request(comment):
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <%(graph)s> {""" % {"graph": LODEPART_GRAPH}
    if comment.get(u"parent_comment_uri") is None:
        req += write_comment_creation_request(comment)
    else:
        req += write_reply_creation_request(comment)
        req += write_opinion_creation_request(comment)
    req += u"}"
    return req

def write_comment_creation_request(comment):
    data = {
        u"uri": comment[u"uri"],
        u"content": escape_text(comment[u"content"]),
        u"lang": LANG_CODE3[comment[u"lang"]],
        u"opinion": COMMENT_NOTE[comment[u"opinion"]],
        u"author": comment[u"author_uri"],
        u"timestamp": comment[u"timestamp"].strftime(u"%Y%m%d%H%M%S"),
        u"doc_part": comment[u"doc_part_uri"],
    }
    req = u"""
  <%(uri)s> a sioc:Post .
  <%(uri)s> sioc:content "%(content)s\"@%(lang)s ;
            sioc:note "%(opinion)s"^^rdfs:Literal ;
            sioc:has_creator <%(author)s> ;
            sioc:created_at "%(timestamp)s" ;
            sioc:has_container <%(doc_part)s> ;
            lodep:num_like "0"^^xsd:nonNegativeInteger ;
            lodep:num_dislike "0"^^xsd:nonNegativeInteger .
""" % data
    return req

def write_reply_creation_request(comment):
    data = {
        u"uri": comment[u"uri"],
        u"content": escape_text(comment[u"content"]),
        u"lang": LANG_CODE3[comment[u"lang"]],
        u"author": comment[u"author_uri"],
        u"timestamp": comment[u"timestamp"].strftime(u"%Y%m%d%H%M%S"),
        u"parent": comment[u"parent_comment_uri"],
    }
    req = u"""
  <%(uri)s> a sioc:Post .
  <%(uri)s> sioc:content "%(content)s"@%(lang)s ;
            sioc:has_creator <%(author)s> ;
            sioc:created_at "%(timestamp)s" ;
            lodep:num_like "0"^^xsd:nonNegativeInteger ;
            lodep:num_dislike "0"^^xsd:nonNegativeInteger .
  <%(parent)s> sioc:has_reply <%(uri)s> .
""" % data
    return req

def write_opinion_creation_request(comment):
    if comment[u"opinion"] == MIX_OPINION:
        return u""
    data = {
        u"uri": comment[u"opinion_uri"],
        u"author": comment[u"author_uri"],
        u"timestamp": comment[u"opinion_timestamp"].strftime(u"%Y%m%d%H%M%S"),
        u"opinion": COMMENT_NOTE[comment[u"opinion"]],
        u"parent": comment[u"parent_comment_uri"],
    }
    req = u"""
  <%(uri)s> a sioc:Post .
  <%(uri)s> sioc:has_creator <%(author)s> ;
            sioc:created_at "%(timestamp)s" ;
            sioc:note "%(opinion)s"^^rdfs:Literal ;
            lodep:give_note_to <%(parent)s> .
""" % data
    return req


def save_doc_structure(doc_struct):
    doc_part_uris, linked_couples = find_doc_items_to_save(doc_struct)
    save_items_in_repository(doc_part_uris, u"document parts",
                             build_doc_part_creation_request)
    save_items_in_repository(linked_couples, u"document parent links",
                             build_parent_link_creation_request)


def build_doc_part_creation_request(doc_part_uri):
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <%(graph)s> {
  <%(uri)s> a sioc:Thread .
  <%(uri)s> lodep:num_items_no "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_no_na "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_yes "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_yes_na "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_mixed "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_mixed_na "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_total "0"^^xsd:nonNegativeInteger ;
            lodep:num_items_total_na "0"^^xsd:nonNegativeInteger .
}""" % {u"graph": LODEPART_GRAPH, u"uri": doc_part_uri}
    return req

def build_parent_link_creation_request(linked_couple):
    data = {
        u"uri": linked_couple[0],
        u"parent": linked_couple[1],
        u"graph": LODEPART_GRAPH,
    }
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
INSERT IN GRAPH <%(graph)s> {
  <%(uri)s> sioc:has_parent <%(parent)s> .
}""" % data
    return req


def get_doc_parts_and_parents_uri(doc_part, doc_lang_root_uri = None):
    doc_parts_uri = [] # couples (doc_part_uri, parent_doc_part_uri)
    if doc_lang_root_uri is None:
        # Highest level in the tree (whole document)
        doc_parts_uri.append( (doc_part[u"uri"], None) )
        doc_lang_root_uri = doc_part[u"uri"]
    else:
        doc_parts_uri.append( (doc_part[u"uri"], doc_lang_root_uri) )
    for child_part in doc_part.get(u"children", []):
        doc_parts_uri.extend(
            get_doc_parts_and_parents_uri(child_part, doc_lang_root_uri) )
    return doc_parts_uri

def find_doc_items_to_save(doc_struct):
    """
    Given a document structure (containing the document in all the
    processed languages), finds the document parts that don't exist
    in the repository and the missing links between these parts and
    their parent.
    """
    doc_parts_uri = []
    for lang, lng_doc_struct in doc_struct.items():
        doc_parts_uri.extend(get_doc_parts_and_parents_uri(lng_doc_struct))
    processed_uris = set()
    doc_parts_to_create = []
    parent_links_to_weave = []
    for doc_part_uri, doc_lang_root_uri in doc_parts_uri:
        if doc_lang_root_uri is None:
            # Whole documents (highest level of the tree) are already in the
            # repository.
            continue
        if doc_part_uri in processed_uris:
            continue
        # check if doc_part already exists in repository
        req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT COUNT(*) AS ?cnt
WHERE {
  <%(uri)s> a sioc:Thread .
}""" % {u"uri": doc_part_uri}
        result = virtuoso_read(req)
        processed_uris.add(doc_part_uri)
        if int(result[0][u"cnt"]) == 0:
            # Current PHP application stores a strange tree:
            # doc_part is child of generic_doc_part (without language
            # specification) and generic_doc_part is child of
            # doc_lang_root (whole document ith language specification)
            generic_doc_part_uri = doc_part_uri[:doc_part_uri.rfind(u"/")]
            # Create doc_part and weave parent link
            doc_parts_to_create.append(doc_part_uri)
            parent_links_to_weave.append( (doc_part_uri, generic_doc_part_uri) )
            # Check if generic_doc_part_uri has already been created
            if generic_doc_part_uri in processed_uris:
                # generic_doc_part exists; only weave parent link
                parent_links_to_weave.append(
                    (generic_doc_part_uri, doc_lang_root_uri) )
            else:
                processed_uris.add(generic_doc_part_uri)
                req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT COUNT(*) AS ?cnt
WHERE {
  <%(uri)s> a sioc:Thread .
}""" % {u"uri": generic_doc_part_uri}
                result = virtuoso_read(req)
                if int(result[0][u"cnt"]) == 0:
                    # Create generic_doc_part and weave parent link
                    doc_parts_to_create.append(generic_doc_part_uri)
                    parent_links_to_weave.append(
                        (generic_doc_part_uri, doc_lang_root_uri) )
                else:
                    # generic_doc_part exists; only weave parent link
                    parent_links_to_weave.append(
                        (generic_doc_part_uri, doc_lang_root_uri) )
    return doc_parts_to_create, parent_links_to_weave


def escape_text(text):
    result = text
    for old_char, new_char in ( (u"\r\n", u"\n"), (u"\\", u"\\\\"),
                                (u"\"", u"\\\""), (u"'", u"\\'") ):
        result = result.replace(old_char, new_char)
    result = escape(result)
    result = result.replace(u"\n", u" <br/>")
    return result
