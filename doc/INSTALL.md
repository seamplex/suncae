# Installing and setting up SunCAE

> [!TIP]
> If you just want to use SunCAE without installing it, you can do so with the [live demo](https://www.caeplex.com/suncae).

> [!NOTE]
> Mind the license of SunCAE itself and the license of all the related packages that SunCAE uses to make sure you are not infringing any license.

This document explains how to set up [SunCAE](https://www.seamplex.com/suncae) so as to serve one or more clients.
A basic installation can be done relatively simple, even without understanding the meaning of the commands.
Keep in mind that a full-fledged installation being able to serve different users might need deep understanding of networking administration and operating systems details. 

## Architectures

The code is aimed at being run on Unix systems. Specifically, Debian GNU/Linux.
There might be ways of making SunCAE run on other architectures.
If you happen to know how, please help us by explaining how.

## Cloning the repository

The first step would be to clone the SunCAE repository:

```
git clone https://github.com/seamplex/suncae
cd suncae
```


## Dependencies

The repository only hosts particular code and files which are not already available somewhere else.
The latter include

 * meshers and solvers executables (e.g. `gmsh`, `feenox`, `ccx`, etc.)
 * Javascript libraries (e.g. `x3dom.js`)
 * CSS and fonts (e.g. `bootstrap.css`)


### Common

SunCAE needs some functionality which is provided by packages which are commonly available in the most common GNU/Linux distribution repositories. Ranging from the web server itself, script interpreters (e.g. PHP and Bash) and other standard Unix utilities, this line (or a similar one for a non-Debian distribution) should be enough:

```
sudo apt-get install git unzip patchelf wget php-cli php-yaml gnuplot
```

### Particular

The (free and open source) meshers, solvers and required libraries and fonts can be downloaded by executing the `deps.sh` script in SunCAE's root directory:

```
./deps.sh
```

> [!IMPORTANT]
> Run the script from SunCAE's root directory, i.e.
>
> ```
> ./deps.sh
> ```
>
> and **not** from the parent (or any other directory) like
>
> ```
> suncae/dep.sh
> ```

> [!TIP]
> The script will try to download and copy the dependencies inside SunCAE's directories (ignored by Git) only if they are not already copied. To force the download and copy (say if the version changed), you can either delete the dependencies or pass `--force` to `deps.sh`
>
> ```
> ./deps.sh --force
> ```


## The web server

SunCAE can be hosted with any web server capable of executing PHP scripts.
The main entry point is under directory `html`.

All the user information is stored as files under the directory `data`.
That is to say, there is not **database** (either SQL or Mongo-like).
Just plain (Git-tracked) files.

> [!TIP]
> Backups are as simple as `cp`ing (or `rsync`ing, `tar`ring, etc.) the directory `data` somewhere else.



### PHP's internal web server

The `php-cli` package includes a simple web server which is enough to host SunCAE for single-user mode (and it is even handy for debugging purposes).
Just run `php` with the `-S` option. Choose an available port and pass the `html` directory in the `-t` option (or go into the `html` directory and run `php -S` from there without `-t`):

```terminal
php -S localhost:8000 -t html
```

> [!IMPORTANT]
> The first time that SunCAE needs to use the `data` directory, it will be created and owned by the user running the server, which in this case will be the user that ran `php`.
> Mind ownerships and permissions if you then change from the internal web server to a professional one such as Apache.

### Apache

Configure Apache to serve the `html` directory in SunCAE's repository.
By default, Apache's root directory is `/var/www/html`.

A quick hack is to make sure that SunCAE’s  [`html`](html) directory is available to be served. For instance, in the default installation you could do

```terminal
ln -s html /var/www/html/suncae
```

and then SunCAE would be available at <http://localhost/suncae>.

> [!WARNING]
> Mind Apache's policies about symbolic links. They are not straightforward, so symlinking SunCAE's `html` directory into Apache's `html` directory might not work out of the box.


 * If you do not have experience with Apache, you might want to delete the default `/var/www` tree and clone SunCAE there.
 * If you have experience with Apache, there is little more to add.


> [!IMPORTANT]
> The first time that SunCAE needs to use the `data` directory, it will be created and owned by the user running the server, which in this case by default is `www-data`.
> Mind ownerships and permissions when accessing `data`.

### Other servers

We do not know.


## Stack configuration

The file [`conf.php`](../conf.php) in SunCAE's root directory controls the choices of the implementations of the different components for the current instance of SunCAE being served:

```php
$auth = "single-user";
$ux = "faster-than-quick";
$cadimporter = "upload";
$cadprocessor = "gmsh";
$runner = "local";
$max_nodes = 100e3;
```

This means that

 1. the same server can change the implementations by changing the content of `conf.php` dynamically
 2. different servers (or the same server with different entry points) can serve different implementations by serving different `html` directories whose parent's `conf.php` is different.
 3. any other combination is also possible, e.g. an interactive HTML-based panel that modifies `conf.php` on demand or that clones a new instance of SunCAE in an arbitrary location (and configures Apache to serve it).

Read the [Design Manual](design.md) for details about what each of the definitions mean.

