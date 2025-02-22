# README

[![Release](https://img.shields.io/github/v/release/jcdenis/FrontendSession?color=lightblue)](https://github.com/JcDenis/FrontendSession/releases)
![Date](https://img.shields.io/github/release-date/jcdenis/FrontendSession?color=red)
[![Dotclear](https://img.shields.io/badge/dotclear-v2.33-137bbb.svg)](https://fr.dotclear.org/download)
[![Dotaddict](https://img.shields.io/badge/dotaddict-official-9ac123.svg)](https://plugins.dotaddict.org/dc2/details/FrontendSession)
[![License](https://img.shields.io/github/license/jcdenis/FrontendSession?color=white)](https://github.com/JcDenis/FrontendSession/src/branch/master/LICENSE)

## ABOUT

_FrontendSession_ is a plugin for the open-source web publishing software called [Dotclear](https://www.dotclear.org).

> Allow session on frontend.

## REQUIREMENTS

* Dotclear 2.33
* PHP 8.1+
* Dotclear admin permission for configuration

## USAGE

First install _FrontendSession_, manualy from a zip package or from 
Dotaddict repository. (See Dotclear's documentation to know how do this)

Once it's done you can manage FrontendSession option from blog preferences.

There are templates for public page. These template is adapted to 
default Dotclear's theme. (based on dotty template set)
If you want to create your own template for your theme, 
copy files from FrontendSession/default-templates 
to your theme tpl path and adapt them.

* This plugin manage sign in, sign up, sign out, and session on public pages, nothing more.
* User must have __FrontendSession__ permission on blog and the __enbaled__ status to sign in.
* The registration form create user with this permission and a __pending__ status.
* Features enabled by session must be done by others plugins.
* Using session on Frontend reduces cache system to near zero.

## LINKS

* [License](https://github.com/JcDenis/FrontendSession/src/branch/master/LICENSE)
* [Packages & details](https://github.com/JcDenis/FrontendSession/releases) (or on [Dotaddict](https://plugins.dotaddict.org/dc2/details/FrontendSession))
* [Sources & contributions](https://github.com/JcDenis/FrontendSession)
* [Issues & security](https://github.com/JcDenis/FrontendSession/issues)

## CONTRIBUTORS

* Jean-Christian Denis (author)

You are welcome to contribute to this code.
