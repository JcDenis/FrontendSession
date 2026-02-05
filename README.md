# README

[![Release](https://img.shields.io/github/v/release/jcdenis/FrontendSession?color=lightblue)](https://github.com/JcDenis/FrontendSession/releases)
![Date](https://img.shields.io/github/release-date/jcdenis/FrontendSession?color=red)
[![Dotclear](https://img.shields.io/badge/dotclear-v2.36-137bbb.svg)](https://fr.dotclear.org/download)
[![Dotaddict](https://img.shields.io/badge/dotaddict-official-9ac123.svg)](https://plugins.dotaddict.org/dc2/details/FrontendSession)
[![License](https://img.shields.io/github/license/jcdenis/FrontendSession?color=white)](https://github.com/JcDenis/FrontendSession/blob/master/LICENSE)

## ABOUT

_FrontendSession_ is a plugin for the open-source web publishing software called [Dotclear](https://www.dotclear.org).

> Allow session on frontend.

## REQUIREMENTS

* Dotclear 2.36
* PHP 8.1+
* Dotclear admin permission for configuration

## USAGE

First install _FrontendSession_, manually from a zip package or from 
Dotaddict repository. (See Dotclear's documentation to know how do this)

Once it's done you can manage FrontendSession option from blog preferences.

There is a template for public page. 
This template is adapted to default Dotclear's theme. 
If you want to create your own template for your theme,  
copy file from FrontendSession/default-templates 
to your theme tpl path and adapt it.

* This plugin manages sign in, sign up, sign out, and session on public pages, nothing more.
* User must have __FrontendSession__ permission on blog and the __enabled__ status to sign in.
* The registration form creates user with this permission and a __pending__ status.
* Features enabled by session must be done by other plugins.
* Using session on Frontend reduces cache system to near zero.
* You can use plugin TelegramNotifier to get notification on new registration

## LINKS

* [License](https://github.com/JcDenis/FrontendSession/blob/master/LICENSE)
* [Packages & details](https://github.com/JcDenis/FrontendSession/releases) (or on [Dotaddict](https://plugins.dotaddict.org/dc2/details/FrontendSession))
* [Sources & contributions](https://github.com/JcDenis/FrontendSession)
* [Issues & security](https://github.com/JcDenis/FrontendSession/issues)

## CONTRIBUTORS

* Jean-Christian Denis (author)

You are welcome to contribute to this code.
