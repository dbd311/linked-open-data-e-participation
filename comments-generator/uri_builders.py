# -*- coding: utf-8 -*-

from const import LODEPART_BASE_URI

def build_group_uri(group_id):
    grp = group_id.replace(u" ", u"_")
    uri = u"{0}/user_group/{1}".format(LODEPART_BASE_URI, grp)
    return uri

def build_user_uri(user_id):
    uri = u"{0}/user/{1}".format(LODEPART_BASE_URI, user_id)
    return uri

def extract_user_id(uri):
    base_uri = u"{0}/user".format(LODEPART_BASE_URI)
    assert uri.startswith(base_uri), \
        u"User URI wrongly formatted: {0}".format(uri)
    return uri[len(base_uri)+1:]

def build_doc_part_uri(doc_base_uri, doc_part_id):
    uri = u"{0}/{1}".format(doc_base_uri, doc_part_id)
    return uri

def build_comment_uri(doc_part_id, user_id, timestamp):
    tstamp = timestamp.strftime(u"%Y%m%d%H%M%S")
    uri = u"{0}/posts/{1}/{2}/{3}".format(
        LODEPART_BASE_URI, user_id, tstamp, doc_part_id)
    return uri

