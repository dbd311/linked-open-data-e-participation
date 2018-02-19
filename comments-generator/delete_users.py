# -*- coding: utf-8 -*-

from const import LODEPART_USERS_GRAPH, TEST_USERS_GROUP_POSTFIX
from uri_builders import build_group_uri
from sparql_utils import virtuoso_write, virtuoso_read


def run():
    # Delete all test users
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
WITH <%(graph)s> DELETE {
  ?user ?p ?o
} WHERE {
  ?user sioc:member_of ?grp .
  ?user ?p ?o
  FILTER (
    STRENDS(STR(?grp), "%(grp_postfix)s")
  )
}""" % {u"graph": LODEPART_USERS_GRAPH,
        u"grp_postfix": TEST_USERS_GROUP_POSTFIX.replace(u" ", u"_"), }
    resp =virtuoso_write(req)
    # Also delete all ghost users
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
WITH <%(graph)s> DELETE {
  ?user ?p ?o
} WHERE {
  ?user foaf:name "" ;
        foaf:familyName "" .
  ?user ?p ?o .
}""" % {u"graph": LODEPART_USERS_GRAPH, }
    resp =virtuoso_write(req)


if __name__ == "__main__":
    run()
