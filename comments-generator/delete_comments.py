# -*- coding: utf-8 -*-

import sys

from const import LODEPART_GRAPH, TEST_USERS_GROUP_POSTFIX
from uri_builders import build_group_uri
from sparql_utils import virtuoso_write, virtuoso_read
from comments_props_updating_utils import (update_comments_opinion_counts,
                                           update_doc_parts_opinion_counts)


def run():
    sys.stdout.write(u"Getting all test users... \n")
    # Get all test users
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT ?user
WHERE {
  ?user sioc:member_of ?grp .
  FILTER (
    STRENDS(STR(?grp), "%(grp_postfix)s")
  )
}""" % {u"grp_postfix": TEST_USERS_GROUP_POSTFIX.replace(u" ", u"_"), }
    results = virtuoso_read(req)
    # Delete all the posts of these users
    sys.stdout.write(u"Deleting all comments written by these users... \n")
    for res in results:
        res[u"graph"] = LODEPART_GRAPH
        req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
WITH <%(graph)s> DELETE {
  ?post ?p ?o
} WHERE {
  ?post sioc:has_creator <%(user)s> .
  ?post ?p ?o .
}""" % res
        virtuoso_write(req)
    # Delete all the document parts that have no comment and that aren't parent
    # of a document part with comments.
    sys.stdout.write(u"Deleting all document parts that now have no comment... "
                     u"\n")
    sel_req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT DISTINCT ?doc_part
WHERE {
  ?doc_part a sioc:Thread .
  FILTER (
    NOT EXISTS {
      ?child sioc:has_parent ?doc_part .
    }
    &&
    NOT EXISTS {
      ?post sioc:has_container ?doc_part .
    }
  )
}"""
    # Selects the document parts with no comment and no child
    results = virtuoso_read(sel_req)
    while len(results) > 0:
        # Delete this document parts, then selects the document parts with
        # no comment and no child and so on
        for res in results:
            res[u"graph"] = LODEPART_GRAPH
            req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
WITH <%(graph)s> DELETE {
  <%(doc_part)s> ?p ?o
} WHERE {
  <%(doc_part)s> ?p ?o
}""" % res
            virtuoso_write(req)
        results = virtuoso_read(sel_req)
    # Finally update the opinion counts on comments and document parts
    sys.stdout.write(u"Updating counts of opinions on comments ...\n")
    update_comments_opinion_counts()
    sys.stdout.write(u"Updating counts of opinions on document parts ...\n")
    update_doc_parts_opinion_counts()


if __name__ == "__main__":
    run()
