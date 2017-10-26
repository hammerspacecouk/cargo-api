#!/bin/sh

sudo ln -s /var/www/api.www.planetcargo.live/script/worker/cargo-arrivals.service /etc/systemd/system/cargo-arrivals.service

sudo systemctl daemon-reload
sudo systemctl enable cargo-arrivals.service
sudo systemctl start cargo-arrivals.service
