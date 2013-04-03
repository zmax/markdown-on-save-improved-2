# Markdown on Save Improved 2

WordPress + Markdown + EpicEditor, and it support Github fenced code block. (It compatible with Crayon syntax highlighter plugin)

Contributors: Starck Lin  
Tags: markdown, formatting, mobile  
Requires at least: 3.3  
Tested up to: 3.5  
Stable tag: 1.2   

## Installation

1. download me
2. make a directory is named to `markdown-on-save` on your wordpress plugin folder
3. drop all to there
4. open your wordpress and activate it in Plugins panel.
5. enjoy it!

## Changelog

### 2.5.4

- Support for indentation

### 2.5.3

- Auto save to WP-Editor content

### 2.5.2 

- Improved "Toggle EpicEditor"

### 2.5

- Support Github Fenced code blocks
- Integrated with [EpicEditor](https://github.com/OscarGodson/EpicEditor) v0.2.0
- Compatible with [Crayon](https://github.com/aramkocharyan/crayon-syntax-highlighter) WP-Plugin


# Markdown on Save Improved #
Contributors: mattwiebe  
Tags: markdown, formatting, mobile  
Requires at least: 3.2  
Tested up to: 3.5.1  
Stable tag: 2.4.1  

Markdown + WordPress = writing bliss.

## Description

WordPress will process your post with Markdown unless you tell it not to. You probably won't want to, since writing in Markdown is so awesome. Compatible with mobile apps and 3rd-party blogging tools.

The main difference between this plugin and the [original](http://wordpress.org/extend/plugins/markdown-on-save/) is that this plugin assumes you always want Markdown processing.

The markdown version is stored separately (in the `post_content_formatted` column), so you can deactivate this plugin and your posts won't spew out Markdown, because HTML is stored in the `post_content` column, just like normal. This is also much faster than doing on-the-fly Markdown conversion on every page load. It's only done once! When you re-edit the post, the markdown version is swapped into the editor for you to edit.

## Installation

1. Upload the `markdown-on-save-improved` folder to your `/wp-content/plugins/` directory
2. Activate the "Markdown On Save Improved" plugin in your WordPress administration interface
3. Create a new post with Markdown.
4. Your post can be edited using Markdown, but will save as processed HTML on the backend.

## Screenshots

1. The meta box where you can disable Markdown formatting or convert Markdown to HTML.

## Frequently Asked Questions

### How do I use Markdown syntax?

Please refer to this resource: [PHP Markdown Extra](http://michelf.com/projects/php-markdown/extra/).

### How do I disable Markdown processing?

In most cases this should be unnecessary, since Markdown ignores existing HTML. But if Markdown for some reason disturbs your HTML when making an edit to a non-Markdown post, either check the "Disable Markdown formatting" checkbox, or put a `<!--no-markdown-->` HTML comment in your post somewhere (useful when using a mobile or 3rd party blogging app).

### How do I enable Markdown processing on a custom post type?

Just add `add_post_type_support( 'your-post-type', 'markdown-osi' );` to your [functionality plugin](http://wpcandy.com/teaches/how-to-create-a-functionality-plugin/) `functions.php` file. Note that it must be hooked to the `init` action like this:

    add_action( 'init', 'your_prefix_add_markdown_support' );
    function your_prefix_add_markdown_support(){
        add_post_type_support( 'your-post-type', 'markdown-osi' );
    }

Note that posts and pages are supported by default.

### How do I convert an existing post to Markdown? 

There is an experimental checkbox in the post editor to convert your old HTML post to Markdown using [Markdownify](http://milianw.de/projects/markdownify/). **Check it at your own risk.** Make sure you have revisions on or backups. If you're relying on specifically crafted HTML, it might get destroyed. But it might be cool.

### What if I don't want to see that Markdown meta box at all?

I see you love Markdown greatly. You have chosen wisely. Add the following constant to your wp-config.php file:

    define( 'SD_HIDE_MARKDOWN_BOX', true );

Note that you can still disable Markdown formatting with a `<!--no-markdown-->` HTML comment. HTML to Markdown conversion will be impossible.

### What happens if I decide I don't want this plugin anymore?

Just deactivate it. The Markdown version is stored separately, so without the plugin, you'll just revert to editing the HTML version.

## Changelog

### 2.4.1

* Compatibility with other plugins that might load Markdown Extra (such as [GitHub-Flavored Markdown Comments](http://wordpress.org/extend/plugins/github-flavored-markdown-comments/))

### 2.4

* Require `add_post_type_support( 'post_type', 'markdown-osi' );` to enable Markdown on custom post types. This makes it play nicely with Jetpack's Custom CSS module, which stores your CSS in a custom post type.

### 2.3

* Update to Markdown Extra 1.2.6 [Markdown Extra release notes](http://michelf.ca/projects/php-markdown/#version-history)
* Add relevant `wp.*` XML-RPC methods for full remote posting/editing capability
* Remote posting fixes
* Consistent WordPress coding style

### 2.2

* Better backwards-compatibility for non-Markdown posts that were written before installing this plugin. (i.e. don't turn off wpautop except when needed.)

### 2.1

* Favour Markdown formatting in all cases, turn off wpautop. Ensures that paragraphs and line breaks are handled according to the [Markdown documentation](http://daringfireball.net/projects/markdown/syntax#p).
* Maintain oEmbed auto-embedding

### 2.0.1

* Disable visual editor when editing Markdown

### 2.0

* Move to opt-out model
* Add experiemental HTML to Markdown conversion using [Markdownify](http://milianw.de/projects/markdownify/)

### 1.0.2

* Fix bug where desired backslashes went missing

### 1.0.1

* Add stripslashes to let Markdown links with titles get parsed.

### 1.0

* Initial forked release

### Upgrade Notice

### 1.0.1
* The next release will move to an opt-out, rather than opt-in approach, as Mark Jaquith has merged the XML-RPC stuff back into [Markdown on Save](http://http://wordpress.org/extend/plugins/markdown-on-save/)



# Markdown on Save #
Contributors: markjaquith  
Donate link: http://txfx.net/wordpress-plugins/donate  
Tags: markdown, formatting  
Requires at least: 3.3  
Tested up to: 3.5  
Stable tag: 1.2  

Allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately, so you can deactivate this plugin any time.

## Description ##

This plugin allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately (in the `post_content_formatted` column), so you can deactivate this plugin and your posts won't spew out Markdown, because HTML is stored in the `post_content`, just like normal. This is also much faster than doing on-the-fly Markdown conversion on every page load. It's only done once! When you re-edit the post, the markdown version is swapped into the editor for you to edit. If something external updates the post content, you'll lose the Markdown version.

## Installation ##

1. Upload the `markdown-on-save` folder to your `/wp-content/plugins/` directory

2. Activate the "Markdown On Save" plugin in your WordPress administration interface

3. Create a new post with Markdown, and check the "This post is formatted with Markdown" box.

4. Done! Now that post can be edited using Markdown, but will save as processed HTML on the backend.

## Screenshots ##

1. The meta box where you designate a post as containing Markdown. This is the only UI for the plugin!

## Frequently Asked Questions ##

### How do I use Markdown syntax? ###

Please refer to this resource: [http://michelf.com/projects/php-markdown/extra/](PHP Markdown Extra).

### What happens if I uncheck the Markdown box? ###

Your post will no longer be interpreted as Markdown, and you may have to alter the post to remove Markdown formatting.

### What happens if I decide I don't want this plugin anymore? ###

Just deactivate it. The Markdown version is stored separately, so without the plugin, you'll just revert to editing the HTML version.

## Changelog ##
### 1.2 ###
* Update PHP Markdown Extra to 1.2.6
* Keep track of which revisions were Markdown and which were not
* Restore old revisions properly, including Markdown status
* Fix a slashing bug that would prevent link titles from parsing
* Fix a bug related to autosave
* Use Dustin Curtis' Markdown logo as a toggle
* Work on the WP-specific XML-RPC API in addition to the generic API

### 1.1.5 ###
* Fix a `stripslashes()` error

### 1.1.4 ###
* XML-RPC support (use <!--markdown--> to enable Markdown mode)

### 1.1.3 ###
* Disables the Visual Editor if the post being edited is in Markdown mode

### 1.1.2 ###
* Fix a slashes bug which would cause link titles to fail
* Enable Markdown when posting remotely by using <!--markdown--> anywhere in post content

### 1.1.1 ###
* Fix bug which made the metabox show up on the Dashboard

### 1.1 ###
* Some extra nonce protection
* Enable the plugin for all content types, not just posts

### 1.0 ###
* First public version
* Fixed a regex bug that could break current menu highlighting. props skarab

## Upgrade Notice ##
### 1.1.5 ###
Update to fix issues with slashes disappearing.

### 1.1.4 ###
Upgrade to use Markdown over XML-RPC (use <!--markdown--> to enable it)

### 1.1.3 ###
Upgrade to fix the bug that caused "titles" in links to fail parsing and to disable the visual editor in Markdown mode

### 1.1.2 ###
Upgrade to fix the bug that caused "titles" in links to fail parsing

### 1.1.1 ###
Prevents the meta box from mistakenly appearing on the Dashboard

### 1.1 ###
Enables the Markdown option for all content types, instead of limiting it to posts
