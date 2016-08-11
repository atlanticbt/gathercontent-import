# Import existing text content from the web into GatherContent

## Description

This is an example interactive command line application, which takes a url to a `sitemap.xml` file, creates a list of web pages on that website, then parses the text from a chosen CSS selector on each web page and creates a corresponding item in GatherContent.

## Requirements

* PHP 5.5+
* Composer (PHP package management tool)

## Installation

Clone this package from GitHub

```bash
$ git clone https://github.com/gathercontent/api-examples.git
```

Move into this project directory

```bash
$ cd 04-import-from-sitemap
```

Install the package dependencies 

```bash
$ composer install
```

## How to use the application

Execute the application with

```bash
$ bin/scrape
```

You will be prompted for a username and API key (found in your personal settings).

If you are a member of multiple accounts on GatherContent you will be asked to choose which account you are importing to.

You can then choose to create a new project, or import content into an existing project.

Give the application a link to your sitemap.xml file, e.g. http://yourwebsite.com/sitemap.xml

You should see a number of pages that will be scraped.

Next choose a CSS selector - e.g. 'article' or css class '.content', '#main-content' to denote which block of content you want imported.

Then confirm and watch the content be pulled and posted back to GatherContent through the public API.

## Running tests

To run the phpspec test suite run

```bash
$ vendor/bin/phpspec run
```

