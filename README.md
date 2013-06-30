m
=

m is a PHP class that has tools for dumping and reporting.
They are a little gruff, but they are smart about being called on a dev machine vs a live-production server.
At first the behavior was different in case a dump was left in code that made it to the production server.
Later I realized that I could add more complex options as well.
The other key feature that I started with was that the dump told you where in the code it came from, so that it could be removed easily.

There is a working demo at <a href="http://sarumino.com/m">sarumino.com</a>.

If you are on my team, there is a 'dev' branch - please use it on your local dev boxes. I'll be implementing it soon on our staging servers.

Installation
-----
Of course, put the files somewhere sensible. They do not need to be in webroot, but the most sensitive things you might put in the ini files are your own email addresses or names of global variables.
The only two files needed are m.php and m.ini. 
Make a copy of sample.ini and rename it m.ini. 
Then open it up and supply your email address, so that m can send you reports.

Once those files are in place, you'll need to `include_once('/path/to/m.php')`
Feel free to do this on every request or have an auto_loader handle it.

Then there is one tricky thing left to do...
I don't necessarily know the best way to do this, so please make suggestions. 
Basically, m needs to know if it is on a production server or a development server.
There are many ways to do this, but this is what it is at right now; m looks for a boolean value in the server global named `$_SERVER['site_is_dev']`
If that value is set and is true, dumps will happen in the middle of your processing.
If it cannot be found, m asssumes it is on a live server and remains silent.

So, for now, you need to have this server global set to true on your development hosts, and undefined or set to false on your production hosts.
I currently use a `auto_prepend_file` to set that on my personal machine.
It could maybe be set by Apache Environment vars (`SetEnv site_is_dev 1`)

I've stuck with the auto_prepend_file and the site_is_dev because I also use those elsewhere in my code.
You may also find them handy.

A simple dump:
```php
m::dump($_SERVER); // click the black box to expose the data
```

If you were unable to tell m that it is in a production environment, you will not see any output... except maybe an HTML comment stating that live dump produces no output.
If you look in the code, you will see there is no method named `dump`. 
But you will see a couple (maybe more someday) of 'sub-methods' related to dump: `dump_dev` and `dump_live`.
When you call `dump` a run-time decision is made about the dev/live status of the server.
Then the appropriate sub-method is called.
You can force m to ignore what kind of server it is on - at your own peril - by calling the 'sub-methods' directly:
```php
m::dump_dev($_SERVER);
```
This is useful if you are generating report emails and you want dumps in them from the live server.
Without the '_dev' the live server would make an empty report for you.

methods
---
All methods are static, so everything will start with `m::` 
Do not try to instantiate m `$m = new m(); //NOPE`


**dump**
```php
m::dump($_SERVER [, $label [, $options_array]]);
```
produces a black box with a big white $label.
Clicking the black box will expose the dumped data.
Arrays and Objects have small +/- signs next to them which toggle collapsing their data.
You can pass an associative array of options as a third argument.

* `$options['collapse']` when false will show the dump completely open on initial display.
* `$options['relevant_backtrace_depth']` - an integer that allows you to skip deeper into the backtrace to help identify the source in your code of this dump.
* `$options['founder']` this is the tag at the bottom of the dump that tells you where the dump is in your code.
m will generate this sentence for you, but you can override it with `'founder'`.
Example usage: `$options['founder'] = 'dumped at the top of ' . __CLASS__ . ' initialization.'` See, sometimes the backtrace is a little tricky. I'm working on that.
* `$options['founder_verb']` alternatively, you can supply just the verbiage, and m will still figure out the line number and source file.


**death**
```php
m::death($_SERVER [, $label [, $options_array]]);
```
This is a wrapper for dump that on live machines will email you a warning (update needed) but on development machines will dump the black box and then die().
Kinda useful when a debugger can't be used. Also; that's how I roll.


**aMail**
```php
m::aMail([any number of arguments]);
```
This generates a report email of some (configurable) environment globals and anything you pass as arguments.
It is handy when you want to know when something happened, but don't want it reported through error channels.
It behaves the same on live or dev and produces no output.
Example: perhaps someone has created an account with a multibyte username.
You are worried that you cannot handle it, so when it happens you want to test it personally.
```php
if($we_gots_multibyte) m::aMail('hey, we got a multibyte user: ' . $user->name, $user);
```


**is_this_still_in_use**
```php
m::is_this_still_in_use([any number of arguments]); // leave a comment with a date.
```
You want to delete the file or function, but you want to be sure it is not still in use somewhere.
Your team is large and poorly organized, and the code is deployed in too many locations.
Add a call here, and you'll get an email about it.
You could do m::aMail(), but that generates reports and runs debug backtraces....
This is nice and simple, incase you put it on a very popular function.
You won't waste billions of cycles on millions of emails.


**help**
```php
if(m::help([$area[, $depth]])) do_something();
```
I don't feel like documenting this one yet.


**decho**
```php
m::decho([any number of arguments]);
```
This is intended for smaller, simpler output.
It doesn't create the black boxes, and also, there is something about collecting them until the end.
They canbe output or emailed or saved to file... (or is this taht other thinig?)
On live servers, this will do nothing.


**reset_javascript_output_check**
```php
m::reset_javascript_output_check();
```
There is a small bit of javascript that is appended to the first dump on a page. If you need it to be attached again, say... if you were generating a report manually with m::dump_dev(), you would need the javascript to be output again.
This will reset the count, and output it on the next call to dump.

Notes
=====
* The css has been moved mostly inline so that reports generated for emails will still look like they should.



Remaining development todos
========================
* lots. finalize the decho and its output.
* iron out debug_backtraces, improve documentation.
* be able to get css and script tags for manual report generation.
* eat several tacos al pastor.
* become aware of expected output. meaning that if this is used on an api that outputs json... what should we do? perhaps there is a configurable method to append data to output, or it gets emailed... who knows?
* configurable colors and styles or stylesheets?



