# -*- coding: utf-8 -*-

import requests, sys

from const import VIRTUOSO_URL, VIRTUOSO_AUTH_URL, VIRTUOSO_LOGIN, VIRTUOSO_PWD
from time import sleep


def extract_json_results(response):
    bnds = response.json().get(u"results", {}).get(u"bindings", [])
    results = []
    for bnd in bnds:
        results.append(
            { var_name : value_dict[u"value"]
              for var_name, value_dict in bnd.items()} )
    return results

def virtuoso_read(req):
    resp = requests.get(VIRTUOSO_URL,
                         params={u"format": u"json", u"query": req})
    if resp.status_code != requests.codes.ok:
        sys.stdout.write(u"\n")
        sys.stderr.write(u"\nVirtuoso end-point returned {0} HTTP Error:\n"
                         u"Request:\n{1}\nResponse:\n{2}\n"
                         u"".format(resp.status_code, req, resp.text))
        raise IOError(u"Error while reading data from Virtuoso repository")
    else:
        return extract_json_results(resp)

def virtuoso_write(req):
    credentials =  requests.auth.HTTPDigestAuth(VIRTUOSO_LOGIN, VIRTUOSO_PWD)
    resp = requests.get(VIRTUOSO_AUTH_URL,
                         params={u"format": u"json", u"query": req},
                         auth=credentials)
    if resp.status_code != requests.codes.ok:
        sys.stdout.write(u"\n")
        sys.stderr.write(u"\nVirtuoso end-point returned {0} HTTP Error:\n"
                         u"Request:\n{1}\nResponse:\n{2}\n"
                         u"".format(resp.status_code, req, resp.text))
        raise IOError(u"Error while writing data in Virtuoso repository")
    else:
        return extract_json_results(resp)


def save_items_in_repository(items_list, items_desc, request_building_function):
    sys.stdout.write(u"   saving {0} {1} ".format(len(items_list), items_desc))
    sys.stdout.flush()
    results = []
    for idx, item in enumerate(items_list):
        if idx % 100 == 0:
            sys.stdout.write(u"\n      {0} ".format(idx))
            sys.stdout.flush()
        elif idx % 10 == 0:
            sys.stdout.write(u".")
            sys.stdout.flush()
        req = request_building_function(item)
        if isinstance(req, (list, tuple)):
            for sub_req in req:
                res = virtuoso_write(sub_req)
                results.append(sub_req)
                results.append(u"---> {0}".format(str(res)))
                sleep(0.05)
        else:
            res = virtuoso_write(req)
            results.append(req)
            results.append(u"---> {0}".format(str(res)))
            sleep(0.05)
    sys.stdout.write(u"\n")
    return results
