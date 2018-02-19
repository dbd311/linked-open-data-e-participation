# -*- coding: utf-8 -*-

from time import sleep

from data_plays_building import build_data_plays
from data_users_building import build_data_users


def run():
    build_data_plays()
    sleep(1)
    build_data_users()


if __name__ == "__main__":
    run()
