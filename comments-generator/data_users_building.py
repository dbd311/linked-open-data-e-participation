# -*- coding: utf-8 -*-

import os, re, json, shutil, random
from os import path as osp

from const import (INPUT_DIR, IMAGES_DIR, DATA_DIR, AVATARS_DIR, USERS_FILENAME,
                   TEST_USERS_GROUP_NAMES, TEST_USERS_PASSWORD, LANGS)


NATIONALITY = {}
for lng in LANGS:
    NATIONALITY[lng["code2"]] = lng["nationality"]


def build_user_id(persona):
    user_id = persona.lower()
    for old_char, new_char in ((u" ", u"_"), (u"\u2019", u"_"),
                               (u"é", u"e"), (u"è", u"e"),
                               (u"ç", u"c"), (u"ä", u"a")):
        user_id = user_id.replace(old_char, new_char)
    return user_id

def define_user_data(user):
    user_id = user[u"user_id"]
    user[u"email"] = u"{0}@test-lodepart.{1}".format(user_id, user[u"lang"])
    words = user[u"persona"].split(u" ")
    if len(words) > 1:
        user[u"first_name"] = words[0]
        user[u"last_name"] = u" ".join(words[1:])
    else:
        user[u"first_name"] = u" "
        user[u"last_name"] = words[0]
    user[u"nationality"] = NATIONALITY[user[u"lang"]]
    user[u"group"] = random.choice(TEST_USERS_GROUP_NAMES)
    user[u"password"] = TEST_USERS_PASSWORD
    user[u"password_confirmation"] = TEST_USERS_PASSWORD

AVATARS = [
    fname for fname in os.listdir(IMAGES_DIR)
    if osp.splitext(fname)[1] == ".png" ]
AVAILABLE_AVATARS = AVATARS[:]
def define_user_avatar(user):
    if not osp.exists(AVATARS_DIR):
        os.makedirs(AVATARS_DIR)
    if len(AVAILABLE_AVATARS) == 0:
        AVAILABLE_AVATARS.extend(AVATARS)
    chosen_avatar = random.choice(AVAILABLE_AVATARS)
    AVAILABLE_AVATARS.remove(chosen_avatar)
    out_filename = osp.join(AVATARS_DIR,
                            u"{0}.png".format(user[u"user_id"]))
    shutil.copy(osp.join(IMAGES_DIR, chosen_avatar),
                out_filename)
    user[u"avatar_filename"] = osp.relpath(out_filename, DATA_DIR)

def collect_users_from_plays():
    users = {}
    for filename in os.listdir(DATA_DIR):
        if filename.startswith(u"_") or osp.splitext(filename)[1] != u".json":
            continue
        with open(osp.join(DATA_DIR, filename)) as stream:
            data = json.load(stream)
            for pers in data[u"personae"]:
                user = {u"persona": pers,
                        u"play_id": data[u"play_id"],
                        u"lang": data[u"lang"]}
                user_id = build_user_id(pers)
                idx = 0
                ftr = re.compile(r"([^0-9]+?)[\-]?([0-9]*)$")
                while user_id in users:
                    base = ftr.match(user_id).groups()[0]
                    idx += 1
                    user_id = u"{0}-{1}".format(base, idx)
                user[u"user_id"] = user_id
                define_user_data(user)
                define_user_avatar(user)
                users[user_id] = user
    return users

def write_users(users):
    with open(osp.join(DATA_DIR, USERS_FILENAME), "w") as stream:
        json.dump(users, stream)


def build_data_users():
    users = collect_users_from_plays()
    write_users(users)

