# -*- coding: utf-8 -*-

import os, json
from os import path as osp
from lxml import etree

from const import INPUT_DIR, DATA_DIR


def read_html_play(filename):
    parser = etree.HTMLParser()
    with open(filename) as stream:
        tree = etree.parse(stream, parser)
        return tree.getroot()

def concat_text_children(html_elt, exclude_elts=None,
                         text_start=None, text_stop=None,
                         exclude_tags=None):
    exclude_elts = exclude_elts or []
    exclude_tags = exclude_tags or []
    flag = (text_start is None)
    text = u""
    if flag:
        text += html_elt.text or u""
    for html_child in html_elt:
        if not flag and html_child == text_start:
            flag = True
        elif not flag:
            continue
        if ( html_child.tag == u"br" and
             (len(text) == 0 or text[-1] != u"\n") ):
            text += u"\n"
        if ( html_child.tag not in exclude_tags and
             html_child not in exclude_elts ):
            text += html_child.text or u""
        if html_child == text_stop:
            flag = False
            continue
        following_txt = html_child.tail or u""
        if html_child.tag == u"br":
            following_txt = following_txt.lstrip()
        text += following_txt
    return text.strip()

def write_json_play(filename, play_id, language, title, author, lines):
    personae = set()
    for line in lines:
        personae.add(line["name"])
    data = {u"lang": language,
            u"play_id": play_id,
            u"title": title,
            u"author": author,
            u"personae": sorted(list(personae)),
            u"lines": lines}
    with open(filename, "w") as stream:
        json.dump(data, stream)

def convert_play_html_to_json(dirname, extract_func, language, title,
                              author):
    if not osp.exists(DATA_DIR):
        os.makedirs(DATA_DIR)
    lines = []
    for filename in os.listdir(osp.join(INPUT_DIR, dirname)):
        if osp.splitext(filename)[1] != ".html":
            continue
        tree = read_html_play(osp.join(INPUT_DIR, dirname, filename))
        lines.extend(extract_func(tree))
    write_json_play(osp.join(DATA_DIR, dirname+u".json"),
                    dirname, language, title, author, lines)
