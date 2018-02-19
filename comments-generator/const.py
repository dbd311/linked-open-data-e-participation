# -*- coding: utf-8 -*-

from os import path as osp
from datetime import datetime


VERSION = u"2016-10-12T14:54:00"

VIRTUOSO_URL = u"http://lod-dk:8890/sparql"
VIRTUOSO_AUTH_URL = u"http://lod-dk:8890/sparql-auth"

VIRTUOSO_LOGIN = u"dba"
VIRTUOSO_PWD = u"dba"

LODEPART_URL = u"http://lod-dk:8000"
LODEPART_REGISTER_URL = LODEPART_URL+u"/auth/register"
LODEPART_ACTIVATION_URL = LODEPART_URL+u"/activate-account"
LODEPART_LOAD_DOC_URL = LODEPART_URL+u"/load-document"

LODEPART_BASE_URI= u"http://lod-dk:8000"

LODEPART_GRAPH = u"http://lodepart/graph"
LODEPART_USERS_GRAPH = u"http://lodepart/graph/users"
TEST_USERS_PASSWORD = u"password"
TEST_USERS_GROUP_POSTFIX = u" (TEST)"
TEST_USERS_GROUP_NAMES = []
for name in (u"Citizen", u"NGO", u"SME", u"Multinational Organisation",
             u"Member of European Parliament"):
    TEST_USERS_GROUP_NAMES.append(u"{0}{1}".format(name,
                                                   TEST_USERS_GROUP_POSTFIX))

DEFAULT_DOC_CREATION_DATE = datetime(2015,1,1)

HERE = osp.abspath(osp.dirname(__file__))
INPUT_DIR = osp.join(HERE, u"input")
DATA_DIR = osp.join(HERE, u"data")
IMAGES_DIR = osp.join(INPUT_DIR, u"images")
AVATARS_DIR = osp.join(DATA_DIR, u"_avatars")
USERS_FILENAME = u"_users.json"

LANGS = [
    {u"code2": u"en",
     u"code3": u"eng",
     u"nationality": u"United_Kingdom"},
    {u"code2": u"fr",
     u"code3": u"fra",
     u"nationality": u"France"},
    {u"code2": u"de",
     u"code3": u"deu",
     u"nationality": u"Germany"},
    {u"code2": u"it",
     u"code3": u"ita",
     u"nationality": u"Italy"},
]

POS_OPINION = u"Positive"
NEG_OPINION = u"Negative"
MIX_OPINION = u"Mixed"

# Opinions are modeled as numbers between 0 and 100.
# An opinion between 0 and POS_THRESHOLD is considered
# to be positive.
# An opinion between NEG_THRESHOLD and 100 is considered
# to be negative.
# An opinion that is neither positive nor negative is mixed.
POS_THRESHOLD = 35
NEG_THRESHOLD = 65

# Opinions are modeled as numbers between 0 and 100.
# They are randomly decided from a gauss distribution. Each nationality has
# its own median for this distribution. Each article has its own deviation
# for this distribution. The article deviation is randomly chosen between
# LOW_DEVIATION and HIGH_DEVIATION.
LOW_DEVIATION = 10
HIGH_DEVIATION = 60

# The number of comments is randomly chosen for each document part. Some parts
# have a number of comments between 0 and COMMENTS_LOW_MAX. Others have a
# number of comments between COMMENTS_LOW_MAX and COMMENTS_HIGH_MAX. The
# proportion between these two categories is given by COMMENTS_LOW_MAX_PERCENT.
COMMENTS_LOW_MAX = 5
COMMENTS_HIGH_MAX = 3 * COMMENTS_LOW_MAX
COMMENTS_LOW_MAX_PERCENT = 80

# Each comment can have replies. The number of replies is randomly chosen
# between 0 and REPLIES_MAX
REPLIES_MAX = 3

# Consecutive comments or replies are separated by a delay that can be either
# a short one or a long one. The proportion between short and long delays is
# given by SHORT_DELAYS_PERCENT. A short delay is randomly chosen between
# 1 second and SHORT_DELAYS_MAX seconds. A long delay is randomly chosen between
# SHORT_DELAYS_MAX seconds and LONG_DELAYS_MAX seconds.
SHORT_DELAYS_MAX = 30*60 # in seconds
LONG_DELAYS_MAX = 200*24*3600 # in seconds
SHORT_DELAYS_PERCENT = 60
