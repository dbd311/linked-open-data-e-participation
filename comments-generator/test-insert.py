from sparql_utils import virtuoso_write
from const import VIRTUOSO_AUTH_URL, VIRTUOSO_LOGIN, VIRTUOSO_PWD


print("Virtuoso Authenticated URL: {0}".format(VIRTUOSO_AUTH_URL))
print("Virtuoso login: {0}".format(VIRTUOSO_LOGIN))
print("Virtuoso password: {0}".format(VIRTUOSO_PWD))


req = """
PREFIX sioc: <http://rdfs.org/sioc/ns#>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_COMMENT> a sioc:Post .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_COMMENT> lodep:num_like "3"^^xsd:nonNegativeInteger .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
PREFIX sioc: <http://rdfs.org/sioc/ns#>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOCUMENT> a sioc:Forum .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOCUMENT> lodep:num_items_total 23 .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
PREFIX sioc: <http://rdfs.org/sioc/ns#>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOC_PART> a sioc:Thread .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOC_PART> lodep:num_items_total "5"^^xsd:nonNegativeInteger .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))

