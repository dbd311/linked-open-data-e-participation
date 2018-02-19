from sparql_utils import virtuoso_write
from const import VIRTUOSO_AUTH_URL, VIRTUOSO_LOGIN, VIRTUOSO_PWD


print("Virtuoso Authenticated URL: {0}".format(VIRTUOSO_AUTH_URL))
print("Virtuoso login: {0}".format(VIRTUOSO_LOGIN))
print("Virtuoso password: {0}".format(VIRTUOSO_PWD))


req = """
DELETE FROM GRAPH <http://lodepart/graph> {
  <_MY_TEST_COMMENT> ?p ?o .
} WHERE {
  <_MY_TEST_COMMENT> ?p ?o .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
DELETE FROM GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOCUMENT> ?p ?o .
} WHERE {
  <_MY_TEST_DOCUMENT> ?p ?o .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))


req = """
DELETE FROM GRAPH <http://lodepart/graph> {
  <_MY_TEST_DOC_PART> ?p ?o .
} WHERE {
  <_MY_TEST_DOC_PART> ?p ?o .
}
"""
print(req)
res = virtuoso_write(req)
print(u"--> {0}".format(str(res)))
