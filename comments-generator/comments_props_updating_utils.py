# -*- coding: utf-8 -*-

import argparse, sys
from functools import partial

from const import LODEPART_GRAPH, VERSION
from sparql_utils import virtuoso_read, save_items_in_repository


def update_comments_opinion_counts(debug=False):
    # Count number of likes and dislikes for each comment in the repository
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SELECT ?post
       COUNT(DISTINCT ?reply) AS ?count_tot
       COUNT(DISTINCT ?pos_reply) AS ?count_pos
       COUNT(DISTINCT ?neg_reply) AS ?count_neg
WHERE {
  ?post a sioc:Post .
  ?reply lodep:give_note_to ?post .
  OPTIONAL {
    ?pos_reply lodep:give_note_to ?post ;
               sioc:note "yes"^^rdfs:Literal .
  }
  OPTIONAL {
    ?neg_reply lodep:give_note_to ?post ;
               sioc:note "no"^^rdfs:Literal .
  }
}"""
    results = virtuoso_read(req)
    # Iterate on the comment and update the ``lodep:num_like`` and
    # ``lodep:num_dislike`` properties with the correct values counted above
    func = partial(build_comment_counts_update_request, debug=debug)
    res = save_items_in_repository(results, u"counts", func)
    return res


def build_comment_counts_update_request(comment_counts_result, debug=False):
    comment_counts_result[u"graph"] = LODEPART_GRAPH
    comment_counts_result[u"count_pos"] = \
                                    int(comment_counts_result[u"count_pos"])
    comment_counts_result[u"count_neg"] = \
                                    int(comment_counts_result[u"count_neg"])
    del_req_1 = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
DELETE FROM <%(graph)s> {
    <%(post)s> lodep:num_like ?old_pos .
}
WHERE {
    <%(post)s> lodep:num_like ?old_pos .
}"""% comment_counts_result
    del_req_2 = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
DELETE FROM <%(graph)s> {
    <%(post)s> lodep:num_dislike ?old_neg .
}
WHERE {
    <%(post)s> lodep:num_dislike ?old_neg .
}"""% comment_counts_result
    ins_req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <%(graph)s> {
  <%(post)s> lodep:num_like "%(count_pos)s"^^xsd:nonNegativeInteger ;
             lodep:num_dislike "%(count_neg)s"^^xsd:nonNegativeInteger .
}
""" % comment_counts_result
    reqs = [del_req_1, del_req_2, ins_req]
    if debug:
        sel_req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
SELECT ?p ?lk ?dlk WHERE {
  ?p lodep:num_like ?lk ;
     lodep:num_dislike ?dlk .
  FILTER (
    ?p = <%(post)s>
  )
}
""" % comment_counts_result
        reqs.append(sel_req)
    return reqs

def update_doc_parts_opinion_counts(debug=False):
    # Count the positive, negative and mixed notes on the comments for each
    # document part in the repository
    req =  u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SELECT ?doc_part
       COUNT(DISTINCT ?comment) AS ?count_tot
       COUNT(DISTINCT ?pos_comment) AS ?count_pos
       COUNT(DISTINCT ?neg_comment) AS ?count_neg
       COUNT(DISTINCT ?mix_comment) AS ?count_mix
WHERE {
  ?comment a sioc:Post ;
           sioc:has_container ?doc_part .
  OPTIONAL {
    ?pos_comment a sioc:Post ;
                 sioc:has_container ?doc_part ;
                 sioc:note "yes"^^rdfs:Literal .
  }
  OPTIONAL {
    ?neg_comment a sioc:Post ;
                 sioc:has_container ?doc_part ;
                 sioc:note "no"^^rdfs:Literal .
  }
  OPTIONAL {
    ?mix_comment a sioc:Post ;
                 sioc:has_container ?doc_part ;
                 sioc:note "mixed"^^rdfs:Literal .
  }
}"""
    results = virtuoso_read(req)
    opinions = {}
    for res in results:
        opinions[res[u"doc_part"]] = {
            u"total": int(res[u"count_tot"]),
            u"positive": int(res[u"count_pos"]),
            u"negative": int(res[u"count_neg"]),
            u"mixed": int(res[u"count_mix"]),
        }
    # Read the document tree structures from the repository thanks to the
    # ``sioc:has_parent`` links
    req = u"""
PREFIX sioc: <http://rdfs.org/sioc/ns#>
SELECT ?doc_part
       ?doc_type
       ?parent_doc_part
       ?parent_doc_type
WHERE {
  ?doc_part a ?doc_type .
  ?doc_part sioc:has_parent ?parent_doc_part .
  ?parent_doc_part a ?parent_doc_type .
}"""
    results = virtuoso_read(req)
    doc_structs = {}
    doc_roots = set()
    doc_not_roots = set()
    for res in results:
        doc_structs.setdefault(res[u"doc_part"], {
            u"uri": res[u"doc_part"],
            u"is_forum": res[u"doc_type"].endswith(u"Forum"),
            u"repo_children": [] })
        doc_structs.setdefault(res[u"parent_doc_part"], {
            u"uri": res[u"parent_doc_part"],
            u"is_forum": res[u"doc_type"].endswith(u"Forum"),
            u"repo_children": [] })
        doc_structs[res[u"parent_doc_part"]][u"repo_children"].append(
            doc_structs[res[u"doc_part"]] )
        doc_roots.discard(res[u"doc_part"])
        doc_not_roots.add(res[u"doc_part"])
        if res[u"parent_doc_part"] not in doc_not_roots:
            doc_roots.add(res[u"parent_doc_part"])
    for uri in doc_not_roots:
        doc_structs.pop(uri)
    # The document structures in the repository are very strange and can not
    # directly be used: "doc_root" is parent of "doc_root/eng", "doc_root/fra";
    # "doc_root/eng" is parent of "doc_root/art_001"; "doc_root/fra" is parent
    # of "doc_root/art_001"; "doc_root/art001" is parent of
    # "doc_root/art001/eng", "doc_root/art001/fra"; etc.
    # Correct the document structures to have: "doc_root" is parent of
    # "doc_root/art_001"; "doc_root/fra" is parent_of "doc_root/art001/fra";
    # "doc_root/eng" is parent of "doc_root/art_001/eng"; "doc_root"
    # has language versions "doc_root/eng", "doc_root/fra"; ""doc_root/art001"
    # has language versions "doc_root/art001/fra",  "doc_root/art001/eng"
    for struct in doc_structs.values():
        correct_doc_structure(struct)
        clean_doc_structure(struct)
    # Count the opinions for each document part inside the structures and
    # insert these counts in the repository
    update_requests = []
    for struct in doc_structs.values():
        set_opinion_counts_in_doc_structure(struct, opinions)
        propagate_opinion_counts_in_doc_structure(struct)
        update_requests.extend(
            build_opinion_counts_update_from_doc_structure(struct,
                                                           debug=debug) )
    res = save_items_in_repository(update_requests, u"counts",
                                   (lambda x: x))
    return res


PROPERTY_NAME = {
    u"direct_tot": u"lodep:num_items_total_na",
    u"direct_pos": u"lodep:num_items_yes_na",
    u"direct_neg": u"lodep:num_items_no_na",
    u"direct_mix": u"lodep:num_items_mixed_na",
    u"total_tot": u"lodep:num_items_total",
    u"total_pos": u"lodep:num_items_yes",
    u"total_neg": u"lodep:num_items_no",
    u"total_mix": u"lodep:num_items_mixed",
}
def build_opinion_counts_update_from_doc_structure(doc_part, saved_uris=None,
                                                   debug=False):
    if saved_uris is None:
        saved_uris = set()
    if doc_part[u"uri"] in saved_uris:
        # Doc part has already been saved
        return []
    saved_uris.add(doc_part[u"uri"])
    doc_part[u"total_tot"] = ( doc_part.get(u"total_pos", 0) +
                               doc_part.get(u"total_neg", 0) +
                               doc_part.get(u"total_mix", 0) )
    doc_part[u"direct_tot"] = ( doc_part.get(u"direct_pos", 0) +
                                doc_part.get(u"direct_neg", 0) +
                                doc_part.get(u"direct_mix", 0) )
    # Deletion request
    reqs = []
    for prop in PROPERTY_NAME.values():
        del_req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
DELETE FROM <%(graph)s> {
    <%(doc_part)s> %(prop)s ?x .
} WHERE {
    <%(doc_part)s> %(prop)s ?x .
}""" % {u"graph": LODEPART_GRAPH, u"doc_part": doc_part[u"uri"], u"prop": prop}
        reqs.append(del_req)
    # Insertion request
    req = u""
    for count_name, prop in PROPERTY_NAME.items():
        count_value = doc_part.get(count_name, 0)
        if count_value == 0 and not doc_part[u"is_forum"]:
            continue
        if doc_part[u"is_forum"]:
            req += u"  <%s> %s \"%d\" .\n" \
                   % (doc_part[u"uri"], prop, count_value)
        else:
            req += u"  <%s> %s \"%d\"^^xsd:nonNegativeInteger .\n" \
                   % (doc_part[u"uri"], prop, count_value)
    if len(req) > 0:
        ins_req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
INSERT IN GRAPH <%(graph)s> {
%(inserts)s
}""" % {u"graph": LODEPART_GRAPH, u"inserts": req, }
        reqs.append(ins_req)
    if debug:
        sel_req = u"""
PREFIX lodep: <https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/>
SELECT ?dp ?tna ?yna ?nna ?mna ?t ?y ?n ?m WHERE {
  OPTIONAL {
    ?dp lodep:num_items_total_na ?tna .
  }
  OPTIONAL {
    ?dp lodep:num_items_yes_na ?yna .
  }
  OPTIONAL {
    ?dp lodep:num_items_no_na ?nna .
  }
  OPTIONAL {
    ?dp lodep:num_items_mixed_na ?mna .
  }
  OPTIONAL {
    ?dp lodep:num_items_total ?t .
  }
  OPTIONAL {
    ?dp lodep:num_items_yes ?y .
  }
  OPTIONAL {
    ?dp lodep:num_items_no ?n .
  }
  OPTIONAL {
    ?dp lodep:num_items_mixed ?m .
  }
  FILTER (
    ?dp = <%(doc_part)s>
  )
}
""" % {u"doc_part": doc_part[u"uri"]}
        reqs.append(sel_req)
    # Recursively save the counts of the children and the language versions
    update_requests = [ reqs ]
    for child_part in doc_part[u"children"]:
        update_requests.extend(
            build_opinion_counts_update_from_doc_structure(child_part,
                                                           saved_uris,
                                                           debug=debug) )
    for lng_part in doc_part.get(u"lang_versions", []):
        update_requests.extend(
            build_opinion_counts_update_from_doc_structure(lng_part,
                                                           saved_uris,
                                                           debug=debug) )
    return update_requests

def set_opinion_counts_in_doc_structure(doc_part, opinions):
    if u"direct_pos" in doc_part:
        # doc_part has already been processed
        return
    # Count opinions directly attached to this document part
    part_opinions = opinions.get(doc_part[u"uri"])
    if part_opinions is not None:
        doc_part[u"direct_pos"] = part_opinions.get(u"positive", 0)
        doc_part[u"direct_neg"] = part_opinions.get(u"negative", 0)
        doc_part[u"direct_mix"] = part_opinions.get(u"mixed", 0)
    else:
        doc_part[u"direct_pos"] = 0
        doc_part[u"direct_neg"] = 0
        doc_part[u"direct_mix"] = 0
    # Recursively count for the children and the language versions
    for child_part in doc_part[u"children"]:
        set_opinion_counts_in_doc_structure(child_part, opinions)
    for lng_part in doc_part.get(u"lang_versions", []):
        set_opinion_counts_in_doc_structure(lng_part, opinions)

def propagate_opinion_counts_in_doc_structure(doc_part):
    if u"total_pos" in doc_part:
        # doc_part has already been processed, return the computed counts
        return (doc_part[u"total_pos"], doc_part[u"total_neg"],
                doc_part[u"total_mix"])
    # Initialize the count with the direct opinions attached to this doc part
    doc_part[u"total_pos"] = doc_part[u"direct_pos"]
    doc_part[u"total_neg"] = doc_part[u"direct_neg"]
    doc_part[u"total_mix"] = doc_part[u"direct_mix"]
    # If possible, recursively propagate the counts from the language versions
    if u"lang_versions" in doc_part:
        for lng_part in doc_part[u"lang_versions"]:
            pos, neg, mix = \
                        propagate_opinion_counts_in_doc_structure(lng_part)
            doc_part[u"total_pos"] += pos
            doc_part[u"total_neg"] += neg
            doc_part[u"total_mix"] += mix
        # Anyway, visit the children to make sure they have retrieved the
        # counts from their language versions
        for child_part in doc_part[u"children"]:
            propagate_opinion_counts_in_doc_structure(child_part)
    # Else recursively propagates the counts from the children (we are on
    # the language version of a part and must retrieve the counts from the
    # language versions of the subparts)
    else:
        for child_part in doc_part[u"children"]:
            pos, neg, mix = \
                        propagate_opinion_counts_in_doc_structure(child_part)
            doc_part[u"total_pos"] += pos
            doc_part[u"total_neg"] += neg
            doc_part[u"total_mix"] += mix
    # Finally return the computed total counts for the recursive calls
    return (doc_part[u"total_pos"], doc_part[u"total_neg"],
            doc_part[u"total_mix"])


def correct_doc_structure(doc_part, lang_postfixes=None):
    child_postfixes = [child_part[u"uri"][child_part[u"uri"].rfind(u"/")+1:]
                       for child_part in doc_part[u"repo_children"]]
    if lang_postfixes is None:
        # We just started and are currently at the document root. The children
        # should be the lang versions of the document.
        # Get all the possible language postfixes from the URI of these children
        lang_postfixes = child_postfixes[:]
    if ( len(child_postfixes) > 0 and
         all(postfix in lang_postfixes for postfix in child_postfixes) ):
        # The children are language versions of this document part
        doc_part[u"lang_versions"] = doc_part[u"repo_children"][:]
        # The real children are the grand children of this document part
        doc_part[u"children"] = []
        child_uris = set()
        for child in doc_part[u"repo_children"]:
            for grand_child in child[u"repo_children"]:
                if grand_child[u"uri"] not in child_uris:
                    doc_part[u"children"].append(grand_child)
                    child_uris.add(grand_child[u"uri"])
    else:
        # The current document part is a language version of a generic part.
        # The parent is this generic part (generic version with no language
        # specification). The children are the generic children of the parent.
        # The grand children are the language versions of these generic
        # children. The real children of this part are therefore the grand
        # children that have the same language postfix as this part.
        lang_postfix = doc_part[u"uri"][doc_part[u"uri"].rfind(u"/")+1:]
        doc_part[u"children"] = []
        child_uris = set()
        for child in doc_part[u"repo_children"]:
            for grand_child in child[u"repo_children"]:
                if ( grand_child[u"uri"] not in child_uris and
                     grand_child[u"uri"][grand_child[u"uri"].rfind(u"/")+1:]
                     == lang_postfix ):
                    doc_part[u"children"].append(grand_child)
                    child_uris.add(grand_child[u"uri"])
    # Recursively iterate on children
    for child_part in doc_part[u"repo_children"]:
        correct_doc_structure(child_part, lang_postfixes=lang_postfixes)

def clean_doc_structure(doc_part):
    if u"repo_children" not in doc_part:
        # doc_part has already been processed
        return
    # Erase unuseful children deduced from repository links
    for child_part in doc_part.pop(u"repo_children"):
        clean_doc_structure(child_part)


def run(debug_filename=None):
    debug = (debug_filename is not None and debug_filename != u"")
    if debug:
        sys.stdout.write(u"Running in debug mode...\n")
    sys.stdout.write(u"Updating counts of opinions on comments ...\n")
    res1 = update_comments_opinion_counts(debug)
    sys.stdout.write(u"Updating counts of opinions on document parts ...\n")
    res2 = update_doc_parts_opinion_counts(debug)
    if debug:
        lines = [
            u"GENERATOR VERSION: {0}\n\n##### Comment replies\n".format(VERSION)
        ]
        lines.extend([u"{0}\n".format(elt) for elt in res1])
        lines.append(u"\n##### Document parts\n")
        lines.extend([u"{0}\n".format(elt) for elt in res2])
        with open(debug_filename, "w") as out:
            out.writelines(lines)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description=(u"Update the counts of comments on the comments (replies) "
                     u"and the document parts: total/positive/negative/mixed") )
    parser.add_argument(u"-d", u"--debug-file",metavar=u"filename", type=str,
                        help=(u"File where all the requests for updating the "
                              u"counts will be saved (for debug purposes)"))
    args = parser.parse_args()
    run(args.debug_file)
