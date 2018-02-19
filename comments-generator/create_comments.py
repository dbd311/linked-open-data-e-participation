# -*- coding: utf-8 -*-

import sys, random, argparse
from datetime import timedelta, datetime

from const import (LANGS, POS_OPINION, NEG_OPINION, MIX_OPINION,
                   POS_THRESHOLD, NEG_THRESHOLD,
                   COMMENTS_LOW_MAX, COMMENTS_HIGH_MAX,
                   COMMENTS_LOW_MAX_PERCENT, REPLIES_MAX,
                   LOW_DEVIATION, HIGH_DEVIATION,
                   SHORT_DELAYS_MAX, LONG_DELAYS_MAX, SHORT_DELAYS_PERCENT,
                   TEST_USERS_GROUP_POSTFIX, LODEPART_BASE_URI,
                   DEFAULT_DOC_CREATION_DATE)
from uri_builders import build_comment_uri
from comments_loading_utils import load_documents, load_personae, load_plays
from comments_saving_utils import save_comments, save_doc_structure
from comments_props_updating_utils import (update_comments_opinion_counts,
                                           update_doc_parts_opinion_counts)
from sparql_utils import virtuoso_read

LANGUAGES = {lng[u"code2"]: lng[u"code3"] for lng in LANGS}


def choose_delay(curr_tstamp):
    delta = (datetime.now() - curr_tstamp).total_seconds()
    if delta <= LONG_DELAYS_MAX:
        delay = random.randint(1, SHORT_DELAYS_MAX)
    elif random.randint(0, 100) < SHORT_DELAYS_PERCENT:
        delay = random.randint(1, SHORT_DELAYS_MAX)
    else:
        delay = random.randint(SHORT_DELAYS_MAX, LONG_DELAYS_MAX)
    return timedelta(seconds=delay)

def choose_language_comments_proportion(langs):
    lang_percents = {}
    langs = langs[:]
    random.shuffle(langs)
    end = 100-11*len(langs)
    for lang in langs[:-1]:
        proportion = random.randint(10, end)
        lang_percents[lang] = proportion
        end -= proportion - 11
    lang_percents[langs[-1]] = end + 11
    return lang_percents

def choose_language_opinion_medians(langs):
    lang_medians = {}
    for lang in langs:
        lang_medians[lang] = random.randint(LOW_DEVIATION, 100-LOW_DEVIATION)
    return lang_medians

def choose_doc_part_deviations(doc_part, lng_code, devs=None):
    if devs is None:
        devs = {}
    doc_id = doc_part[u"id"]
    if doc_id not in devs:
        assert doc_id[-len(lng_code):] == lng_code, \
            u"Unexpected document id: {0}".format(doc_id)
        generic_id = doc_id[:-(len(lng_code)+1)]
        if generic_id not in devs:
            devs[generic_id] = random.randint(LOW_DEVIATION, HIGH_DEVIATION)
        devs[doc_id] = devs[generic_id]
    for child_part in doc_part.get(u"children", []):
        choose_doc_part_deviations(child_part, lng_code, devs)
    return devs

def choose_doc_part_comment_numbers(doc_part, lng_code, lang_proportion,
                                    nums=None, low_max=COMMENTS_LOW_MAX,
                                    high_max=COMMENTS_HIGH_MAX,
                                    low_percent=COMMENTS_LOW_MAX_PERCENT):
    if nums is None:
        nums = {}
    doc_id = doc_part[u"id"]
    if doc_id not in nums:
        assert doc_id[-len(lng_code):] == lng_code, \
            u"Unexpected document id: {0}".format(doc_id)
        generic_id = doc_id[:-(len(lng_code)+1)]
        if generic_id not in nums:
            if random.randint(0, 100) < low_percent:
                nums[generic_id] = random.randint(0, low_max)
            else:
                nums[generic_id] = random.randint(low_max, high_max)
        nums[doc_id] = int(round(nums[generic_id] * lang_proportion / 100.0))
    for child_part in doc_part.get(u"children", []):
        choose_doc_part_comment_numbers(child_part, lng_code, lang_proportion,
                                        nums, low_max, high_max, low_percent)
    return nums


def build_comment(play, play_pers):
    # Extract next line from the play
    if play[u"lines_cursor"] > play[u"lines_max"]:
        play[u"lines_cursor"] = 0
    line = play[u"lines"][play[u"lines_cursor"]]
    # Define new comment
    comment = {u"content": line[u"text"],
               u"author_uri": play_pers[line[u"name"]][u"user_uri"],
               u"author_id": play_pers[line[u"name"]][u"lodepart_user_id"],
    }
    # Increment line cursor
    play[u"lines_cursor"] += 1
    return comment

def generate_comments_for_item(doc_part, plays, pers, lang, creation_date,
                               lang_medians, doc_part_deviations,
                               doc_part_comment_numbers, replies_max,
                               chosen_play=None, parent_comment=None,
                               used_uris=None):
    """
    Generate comments either for a document part or for an existing comment.
    """
    comments = []
    curr_tstamp = creation_date
    if not doc_part[u"accept_comments"]:
        return comments
    if parent_comment is None:
        sys.stdout.write(u".")
        sys.stdout.flush()
        children_number = doc_part_comment_numbers[doc_part[u"id"]]
    else:
        children_number = random.randint(0, replies_max)
    if used_uris is None:
        used_uris = set()
    # Get opinion median and deviation
    opinion_median = lang_medians[lang]
    opinion_deviation = doc_part_deviations[doc_part[u"id"]]
    # Randomly choose a play
    if chosen_play is None:
        chosen_play = random.choice(list(plays[lang].values()))
    play_pers = pers[chosen_play[u"play_id"]]
    # Iterate to create the comments
    for idx in range(0, children_number):
        # Build new comment
        comment = build_comment(chosen_play, play_pers)
        # Choose timestamps of the comment and the opinion (randomly chosen)
        # so as their URIs are uniques
        next_tstamp = curr_tstamp + choose_delay(curr_tstamp)
        while build_comment_uri(doc_part[u"id"], comment[u"author_id"],
                                next_tstamp) in used_uris:
               next_tstamp = curr_tstamp + choose_delay(curr_tstamp)
        curr_tstamp = next_tstamp
        comment[u"opinion_timestamp"] = curr_tstamp
        comment[u"opinion_uri"] = build_comment_uri(doc_part[u"id"],
                                                    comment[u"author_id"],
                                                    curr_tstamp)
        used_uris.add(comment[u"opinion_uri"])
        next_tstamp = curr_tstamp + choose_delay(curr_tstamp)
        while build_comment_uri(doc_part[u"id"], comment[u"author_id"],
                                next_tstamp) in used_uris:
               next_tstamp = curr_tstamp + choose_delay(curr_tstamp)
        curr_tstamp = next_tstamp
        comment[u"timestamp"] = curr_tstamp
        comment[u"uri"] = build_comment_uri(doc_part[u"id"],
                                            comment[u"author_id"],
                                            curr_tstamp)
        used_uris.add(comment[u"uri"])
        if curr_tstamp > datetime.now():
            used_uris.remove(comment[u"opinion_uri"])
            used_uris.remove(comment[u"uri"])
            chosen_play[u"lines_cursor"] -= 1
        # Set various comment properties
        comment[u"lang"] = lang
        comment[u"doc_part_id"] = doc_part[u"id"]
        comment[u"doc_part_uri"] = doc_part[u"uri"]
        # Choose opinion for the comment
        opinion = random.gauss(opinion_median, opinion_deviation)
        if opinion <= POS_THRESHOLD:
            comment[u"opinion"] = POS_OPINION
        elif opinion >= NEG_THRESHOLD:
            comment[u"opinion"] = NEG_OPINION
        else:
            comment[u"opinion"] = MIX_OPINION
        comments.append(comment)
        if parent_comment is not None:
            # Keep a reference to the parent comment this new reply comment is
            # about
            comment[u"parent_comment_uri"] = parent_comment[u"uri"]
        else:
            # Build replies to this new comment about a document part
            comments.extend(
                generate_comments_for_item(
                    doc_part, plays, pers, lang, curr_tstamp, lang_medians,
                    doc_part_deviations, doc_part_comment_numbers,
                    replies_max, chosen_play=chosen_play,
                    parent_comment=comment, used_uris=used_uris)
            )
    # Recursively generate comments for the children document parts
    if parent_comment is None:
        for child_part in doc_part.get(u"children", []):
            comments.extend(
                generate_comments_for_item(
                    child_part, plays, pers, lang, creation_date,
                    lang_medians, doc_part_deviations,
                    doc_part_comment_numbers, replies_max,
                    used_uris=used_uris)
            )
    return comments

def generate_comments(docs, plays, pers, comments_low_max=COMMENTS_LOW_MAX,
                      comments_high_max=COMMENTS_HIGH_MAX,
                      low_max_percent=COMMENTS_LOW_MAX_PERCENT,
                      replies_max=REPLIES_MAX):
    comments = []
    available_langs = list(set(docs.keys()).intersection(set(LANGUAGES.keys())))
    available_langs.sort()
    # Get URIs already used
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT ?comm
WHERE {
   ?comm a sioc:Post .
}"""
    results = virtuoso_read(req)
    used_uris = set(res_line[u"comm"] for res_line in results)
    # Choose parameters for the comments generation
    lang_medians = choose_language_opinion_medians(available_langs)
    lang_props = choose_language_comments_proportion(available_langs)
    doc_part_devs = {}
    doc_part_nums = {}
    for lang in available_langs:
        doc_part_devs = choose_doc_part_deviations(
            docs[lang], LANGUAGES[lang], doc_part_devs)
        doc_part_nums = choose_doc_part_comment_numbers(
            docs[lang], LANGUAGES[lang], lang_props[lang], doc_part_nums,
            low_max=comments_low_max, high_max=comments_high_max,
            low_percent=low_max_percent)
    # Actually generate the comments for all the document parts
    for lang in available_langs:
        doc_struct = docs[lang]
        sys.stdout.write(u"   {0} ".format(lang.upper()))
        sys.stdout.flush()
        comments.extend(
            generate_comments_for_item(
                doc_struct, plays, pers, lang, doc_struct[u"creation_date"],
                lang_medians, doc_part_devs, doc_part_nums, replies_max,
                used_uris = used_uris)
        )
        sys.stdout.write(u"\n")
    return comments


def run(document_uri, comments_low_max, comments_high_max, low_max_percent,
        replies_max, default_creation_date):
    if comments_high_max < comments_low_max:
        sys.stderr.write("Low maximum number of comments ({0}) should be "
                         u"inferior to high maximum number of comments ({1})"
                         u"".format(comments_low_max, comments_high_max))
        sys.exit(1)
    if low_max_percent < 0 or low_max_percent > 100:
        sys.stderr.write("Percent of document parts using low maximum of "
                         u"comments ({0}) should be between 0 and 100"
                         u"".format(low_max_percent))
        sys.exit(1)
    sys.stdout.write(u"Loading the document in the various languages...\n")
    docs = load_documents(document_uri, default_creation_date)
    if len(docs) == 0:
        sys.stderr.write(
            u"\nDocument with URI <{0}> not found in repository!\n\n"
            u"".format(document_uri))
        sys.exit(1)
    sys.stdout.write(u"Loading users' data...\n")
    pers = load_personae()
    sys.stdout.write(u"Loading users' dialog lines...\n")
    plays = load_plays()
    sys.stdout.write(u"Generating comments...\n")
    comments = generate_comments(docs, plays, pers, comments_low_max,
                                 comments_high_max, low_max_percent,
                                 replies_max)
    sys.stdout.write(u"Saving documents structure...\n")
    save_doc_structure(docs)
    sys.stdout.write(u"Saving comments...\n")
    save_comments(comments)
    sys.stdout.write(u"Updating counts of opinions on comments ...\n")
    update_comments_opinion_counts()
    sys.stdout.write(u"Updating counts of opinions on document parts ...\n")
    update_doc_parts_opinion_counts()
    req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
SELECT ?doc_uri ?total_na ?yes_na ?no_na ?mixed_na ?total ?yes ?no ?mixed
WHERE {
  OPTIONAL {
    ?doc_uri lodep:num_items_total_na ?total_na .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_yes_na ?yes_na .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_no_na ?no_na .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_mixed_na ?mixed_na .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_total ?total .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_yes ?yes .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_no ?no .
  }
  OPTIONAL {
    ?doc_uri lodep:num_items_mixed ?mixed .
  }
  FILTER (
    ?doc_uri = <%s>
  )
}""" % document_uri
    res = virtuoso_read(req)
    sys.stdout.write(u"\nEnd of comments creation\n\n")
    sys.stdout.write(u"SPARQL Request:\n{0}\n\n".format(req))
    sys.stdout.write(u"JSON result:\n\n{0}\n\n".format(str(res)))


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description=(u"Generate and save comments about one document in "
                     u"Lodepart application") )
    parser.add_argument(u"document_uri", metavar=u"doc_uri", type=str,
                        help=(u"base URI of the document (without language "
                              u"specifcation). For example: "
                              u"{0}/eli/PROP_REG/2015/0294"
                              u"".format(LODEPART_BASE_URI)) )
    parser.add_argument(u"-l", u"--comments-low-max",metavar=u"Clow", type=int,
                        help=(u"Low maximum number of comments for a given "
                              u"document part. (default: {0})"
                              u"".format(COMMENTS_LOW_MAX)),
                        default=COMMENTS_LOW_MAX)
    parser.add_argument(u"-i", u"--comments-high-max",metavar=u"Chigh",
                        type=int,
                        help=(u"High maximum number of comments for a given "
                              u"document part. (default: {0})"
                              u"".format(COMMENTS_HIGH_MAX)),
                        default=COMMENTS_HIGH_MAX)
    parser.add_argument(u"-p", u"--low-max-percent",metavar=u"Plow",
                        type=int,
                        help=(u"Percent of document parts using low maximum "
                              u"number of comments. (default: {0})"
                              u"".format(COMMENTS_LOW_MAX_PERCENT)),
                        default=COMMENTS_LOW_MAX_PERCENT)
    parser.add_argument(u"-r", u"--replies-max",metavar=u"Rmax", type=int,
                        help=(u"Maximum number of replies for a given comment. "
                              u"(default: {0})".format(REPLIES_MAX)),
                        default=REPLIES_MAX)
    default_date = DEFAULT_DOC_CREATION_DATE.strftime(u"%Y-%m-%d")
    parser.add_argument(u"-d", u"--creation-date",metavar=u"Cdate", type=str,
                        help=(u"Creation date of the document if it is not "
                              u"defined inside the repository. Comments start "
                              u"after this date. (default: {0})"
                              u"".format(default_date)),
                        default=default_date)
    args = parser.parse_args()
    try:
        creation_date = datetime.strptime(args.creation_date, u"%Y-%m-%d")
    except (TypeError, ValueError):
        sys.stderr.write(
            u"Date specified for '--creation-date' argument in wrong format "
            u"({0}).\nUse YYYY-MM-DD format.\n".format(args.creation_date))
        sys.exit(1)
    run(args.document_uri, args.comments_low_max, args.comments_high_max,
        args.low_max_percent, args.replies_max, creation_date)
