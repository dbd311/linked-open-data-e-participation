# -*- coding: utf-8 -*-

import re

from play_conversion_utils import (concat_text_children,
                                   convert_play_html_to_json)


def extract_othello_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//p[b and following-sibling::*[1]/self::dl]")
    for html_pers in html_personae:
        name = html_pers.findtext("b").strip()
        name = name.title()
        if name == "All":
            name = "Iago"
        text = u"\n".join(
            [concat_text_children(html_elt, exclude_tags=[u"i"])
             for html_elt
             in html_pers.xpath("following-sibling::dl[1]/dd")])
        lines.append({u"name": name, u"text": text})
    return lines

def extract_being_earnest_lines(html_root):
    expr = re.compile(r"([^\[^\]]*)(\[[^\[^\]]+\])?(.+)?")
    lines = []
    html_personae = html_root.xpath(
        u".//p[node()[1]/self::b]")
    for html_pers in html_personae:
        name = html_pers.findtext("b").strip()
        name = name.replace(u".", u"").title()
        start = html_pers.xpath("b[1]")[0]
        text = concat_text_children(html_pers, text_start=start,
                                    exclude_elts=[start])
        cleaned_text = u""
        following_txt = text
        while following_txt is not None:
            preceding_txt, didascalie, following_txt = \
                                            expr.match(following_txt).groups()
            cleaned_text += preceding_txt or u""
        cleaned_text = cleaned_text.replace(u"\xa0 ", u" ")
        lines.append({u"name": name, u"text": cleaned_text.strip()})
    return lines

def extract_george_dandin_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//p[b and preceding::h3/span[@class='mw-headline']]")
    for html_pers in html_personae:
        name = html_pers.findtext("b").strip()
        name = name.title()
        start = html_pers.xpath("b[1]/following-sibling::br[1]")[0]
        text = concat_text_children(html_pers, text_start=start)
        lines.append({u"name": name, u"text": text})
    return lines

def extract_le_cid_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//div[span/@class='personnage']")
    html_pers_couples = list(zip(html_personae, html_personae[1:]))
    html_pers_couples.append(
        (html_personae[-1], html_root.find(u"noscript")) )
    for html_pers, next_html_pers in html_pers_couples:
        name = html_pers.findtext("span[@class='personnage']").strip()
        name = name.title()
        texts = []
        for html_txt in html_pers.xpath(u"following::p"):
            if next_html_pers in html_txt.xpath(u"preceding::*"):
                break
            html_excl = html_txt.xpath(
                u"span[@style='visibility:hidden;'] "
                u"| span[@style='color: transparent;']")
            texts.append(
                concat_text_children(html_txt,
                    exclude_elts=html_excl, exclude_tags=[u"i"]) )
        text = u"\n".join([txt for txt in texts if len(txt) > 0])
        lines.append({u"name": name, u"text": text})
    return lines

def extract_affaires_sont_affaires_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//div[span/@class='personnage']")
    for html_pers in html_personae:
        name = html_pers.findtext("span[@class='personnage']").strip()
        name = name.title()
        if u" Et " in name:
            name = name[name.rfind(u" Et ")+4:]
        elif name == u"Voix":
            name = u"Un Ouvrier"
        elif name.startswith(u"La Voix D’"):
            name = name[len(u"La Voix D’"):]
        elif name.startswith(u"La Voix De"):
            name = name[len(u"La Voix De "):]
        text = concat_text_children(
            html_pers.xpath("following-sibling::p[1]")[0],
            exclude_tags=[u"i"])
        lines.append({u"name": name, u"text": text})
    return lines

def extract_kabale_und_liebe_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//p[ node()[1]/self::b or "
        u"      (node()[1]/self::span and node()[2]/self::b) ]")
    for html_pers in html_personae:
        name = html_pers.findtext("b").strip()
        name = name.replace(u":", u"").replace(u".", u"").title()
        if name == u"Alle Bediente":
            next_html_pers = html_personae[html_personae.index(html_pers)+1]
            name = next_html_pers.findtext("b").strip()
            name = name.replace(u":", u"").replace(u".", u"").title()
        start = html_pers.xpath(
            u"b[ preceding-sibling::*[1]/self::i and "
            u"   preceding-sibling::*[2]/self::b and "
            u"   ../*[1]=preceding-sibling::*[2]/self::b ]")
        if len(start) > 0:
            start = start[0]
        else:
            start = html_pers.xpath(u"b[1]")[0]
        text = concat_text_children(html_pers, text_start=start,
                                    exclude_elts=[start],
                                    exclude_tags= [u"i"])
        if len(text) > 0:
            lines.append({u"name": name, u"text": text})
    return lines

def extract_pettegolezzi_lines(html_root):
    lines = []
    html_personae = html_root.xpath(
        u".//dl[dt]")
    for html_pers in html_personae:
        name = html_pers.findtext("dt").strip()
        name = name.title()
        if name in (u"A Due", u"A Tre", u"Tutti"):
            name = html_pers.xpath(u"preceding::dt[1]/text()")[0].strip()
            name = name.title()
        text = concat_text_children(html_pers.xpath(u"dd")[0],
                                    exclude_tags=[u"i"])
        text = text.replace(u"()", u"")
        lines.append({u"name": name, u"text": text})
    return lines


def build_data_plays():
    #convert_play_html_to_json(
    #    u"othello", extract_othello_lines,
    #    u"en", u"Othello", u"William Shakespeare")
    convert_play_html_to_json(
        u"the_importance_of_being_earnest",
        extract_being_earnest_lines,
        u"en", u"The importance of being Earnest", u"Oscar Wilde")
    #convert_play_html_to_json(
    #    u"george_dandin", extract_george_dandin_lines,
    #    u"fr", u"Georges Dandin", u"Molière")
    #convert_play_html_to_json(
    #    u"le_cid",extract_le_cid_lines,
    #    u"fr", u"Le Cid", u"Pierre Corneille")
    convert_play_html_to_json(
        u"les_affaires_sont_les_affaires",
        extract_affaires_sont_affaires_lines,
        u"fr", u"Les affaires sont les affaires", u"Octave Mirbeau")
    convert_play_html_to_json(
        u"kabale_und_liebe", extract_kabale_und_liebe_lines,
        u"de", u"Kabale und Liebe", u"Friedrich von Schiller")
    convert_play_html_to_json(
        u"i_pettegolezzi_delle_done", extract_pettegolezzi_lines,
        u"it", u"I pettegolezzi delle donne", u"Carlo Goldoni")
