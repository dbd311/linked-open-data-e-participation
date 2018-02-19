# -*- coding: utf-8 -*-

import requests, json, sys, hashlib
from os import path as osp
from time import sleep

from const import (LODEPART_REGISTER_URL, LODEPART_ACTIVATION_URL, DATA_DIR,
                   USERS_FILENAME, TEST_USERS_GROUP_POSTFIX)
from uri_builders import build_user_uri
from sparql_utils import virtuoso_read


MIMETYPES = {u".png": u"image/png",
             u".gif": u"image/gif",
             u".jpg": u"image/jpeg"}
DEFAULT_MIMETYPE = "application/octet-stream"


def create_user(user):
    files = {}
    if user.get(u"avatar_filename", u"") != u"":
        filename = osp.join(DATA_DIR, user[u"avatar_filename"])
        files[u"avatar"] = (
            osp.split(filename)[1],
            open(filename, "rb"),
            MIMETYPES.get(osp.splitext(filename)[1], DEFAULT_MIMETYPE) )
    params = {u"lang": user.get(u"lang", u"en")}
    data = {}
    for key in (u"first_name", u"last_name", u"email", u"password",
                u"password_confirmation", u"group", u"nationality"):
        data[key] = user.get(key, u"")
    resp = requests.post(LODEPART_REGISTER_URL, params=params, data=data,
                         files=files)
    if resp.status_code != requests.codes.ok:
        sys.stderr.write(u"\n The server returned a {0} ERROR\n"
                         u"".format(resp.status_code))
    if u"alert alert-danger" in resp.text:
        idx = resp.text.find(u"alert alert-danger")
        sys.stderr.write(u"\n The HTML page contains this ERROR MESSAGE:\n"
                         u"{0}\n".format(resp.text[idx-15:idx+400]))
    return ( (resp.status_code == requests.codes.ok)
             and (u"alert alert-danger" not in resp.text) )


def run():
    data = {}
    with open(osp.join(DATA_DIR, USERS_FILENAME)) as stream:
        data = json.load(stream)
    # Users creation
    users_to_activate = {}
    for idx, user in enumerate(data.values()):
        sys.stdout.write(
            u"Adding user {0} ({1}/{2})...".format(
                user[u"user_id"], idx+1, len(data.values())))
        sys.stdout.flush()
        res = create_user(user)
        if res:
            sys.stdout.write(u" ok\n")
            users_to_activate[user[u"user_id"]] = {
                u"lang": user.get(u"lang", u"en"),
                u"email": user[u"email"],
            }
        else:
            sys.stdout.write(u" ===> ERROR!\n")
        sleep(0.25)
    # Users activation
    sys.stdout.write(u"\n")
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
SELECT ?user ?email ?token WHERE {
  ?user sioc:member_of ?grp ;
        foaf:mbox ?email ;
        lodep:token_id ?token .
  FILTER (
    STRENDS(STR(?grp), "%(grp_postfix)s")
  )
}
""" % {u"grp_postfix": TEST_USERS_GROUP_POSTFIX.replace(u" ", u"_"), }
    results = virtuoso_read(req)
    user_tokens = {res_line[u"email"]:res_line[u"token"]
                   for res_line in results}
    for idx, (user_id, user_data) in enumerate(users_to_activate.items()):
        sys.stdout.write(
            u"Activating user {0} ({1}/{2})...".format(user_id, idx+1,
                                                       len(users_to_activate)))
        sys.stdout.flush()
        md5_email = hashlib.md5(user_data[u"email"].strip().encode(u"ascii"))\
                           .hexdigest()
        params = {u"lang": user_data[u"lang"],
                  u"token": user_tokens[md5_email], }
        resp = requests.get(LODEPART_ACTIVATION_URL, params=params)
        if resp.status_code == requests.codes.ok:
            sys.stdout.write(u" ok\n")
        else:
            sys.stdout.write(u" ===> ERROR!\n")
        sleep(0.25)
    sys.stdout.write(u"\n")


if __name__ == u"__main__":
    run()
