from sparql_utils import virtuoso_read
from const import VIRTUOSO_URL


print("Virtuoso URL: {0}".format(VIRTUOSO_URL))


req = """
SELECT ?s ?p ?o {
  ?s ?p ?o .
  FILTER (
    ?s = <_MY_TEST_COMMENT>
  )
}
"""
print(req)
res = virtuoso_read(req)
print(u"--> {0}".format(str(res)))


req = """
SELECT ?s ?p ?o {
  ?s ?p ?o .
  FILTER (
    ?s = <_MY_TEST_DOCUMENT>
  )
}
"""
print(req)
res = virtuoso_read(req)
print(u"--> {0}".format(str(res)))


req = """
SELECT ?s ?p ?o {
  ?s ?p ?o .
  FILTER (
    ?s = <_MY_TEST_DOC_PART>
  )
}
"""
print(req)
res = virtuoso_read(req)
print(u"--> {0}".format(str(res)))
