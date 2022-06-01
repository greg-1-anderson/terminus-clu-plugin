# Terminus CLU client

[![Unofficial](https://img.shields.io/badge/Pantheon-Unofficial-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#unofficial)

A terminus plugin to create pull requests based on composer.lock updates.

## Requirements

Depends on [Terminus Build Tools](https://github.com/pantheon-systems/terminus-build-tools-plugin), and only works for a Build Tools managed site.

## Installation

- `cd ~/.terminus/plugins`
- `git clone https://github.com/pantheon-systems/terminus-clu-plugin.git` 
- `cd terminus-clu-plugin`
- `composer install --no-dev`

## Commands

`terminus project:clu`

also available:

`terminus project:pull-request:list`

`terminus project:pull-request:create`

`terminus project:pull-request:close <pr-id>`
