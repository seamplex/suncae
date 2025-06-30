# SunCAE design description

SunCAE is split into a front end and a back end.

 * The front end is HTML + Javascript running on a web browser on any operating system (even mobile devices).
 * The back end is PHP + Bash + Python + particular binaries running on one or more Unix servers.

The spirit is that the definition of the CAE problem being solved is stored in a single source of truth as a text file, hopefully the actual solver's input file. The web-based interface should allow both

 1. To update the solver's input file from the status of web-based widgets (e.g. picking faces in the 3D model to hold boundary conditions, entering material properties in text boxes, choosing sdef/ldef from a combo box, etc.), and
 2. To allow the user to edit the input file and then to update the status of web-based widgets from the contents of the input file.
 
For instance, consider the following [FeenoX](https://www.seamplex.com/feenox/) input file:

```
PROBLEM mechanical READ_MESH meshes/9061bd607902d31a8aac5f97a1066504-2.msh

E(x,y,z) = 200e3
nu = 0.3

BC face1  fixed
BC face3  Fx=10e3
```

If the user changes the value of the Young's modulus to `210e3`, the front end issues an AJAX call and the back end updates the file.
Conversely, if the user edits the input file (through a web-based editor the UX has to provide) and changes the value in the file to something else, then the back-end sends instructions to the front end to update the web-based interface.
Moreover, each time the file changes (either because of the user changing a value in the interface or editing the file), a Git commit is issued. Therefore, every single change in the single source of truth is tracked (what? who? when?).

# The SunCAE interface

The SunCAE interface consists of four steps divided into two stages:

 1. New case
    1. CAD import, physics, problem, mesher and solver selection
 2. Case view
    1. Mesh
    2. Problem
    3. Results

The source code of the index page at [html/index.php](../html/index.php) looks like this

```php
include("../conf.php");
include("../auths/{$auth}/auth.php");
include("common.php");
include("case.php");
include("../uxs/{$ux}/index.php");
```

 * The [`conf.php`](../conf.php) file is included first.
 * The authorization scheme `$auth` defined in `conf.php` is included.
   This step should ask for authentication/authorization, set/read cookies, etc.
   PHP's [session_start()](https://www.php.net/manual/en/function.session-start.php) can be used.
   This file should define a non-empty global PHP string `$username`.
   The default [`single-user`](../auths/single-user/auth.php) auth scheme just does
   
   ```php
   $username = "root";
   ```

 * The file `common.php` defines common functions and methods needed by the back end.
   It also defines a PHP variable `$id` coming from either a `GET` or `POST` argument with name `id`:

   ```php
   $id = (isset($_POST["id"])) ? $_POST["id"] : ((isset($_GET["id"])) ? $_GET["id"] : "");
   ```
   
   This should be the `id` of an existing case.
 
 * The file `case.php` reads the metadata for the case `id`. But, if the variable `$id` is empty, it will re-direct the user to the ["New case"](#new-case) page at `new/`:
 
   ```php
   if ($id == "") {
     header("Location: new/");
     exit();
   }
   ```
   
 * If `$id` is not empty, the workflow continues to include the `$ux` scheme defined in `conf.php` which should load case `$id` and show the ["Case view"](#case-view), loading either the mesh, problem or results view depending on the state of the case.

 
## New case

The [`html/new/index.php`](../html/new/index.php) source is 

```php
include("../../conf.php");
include("../../auths/{$auth}/auth.php");
include("../common.php");
include("../../uxs/{$ux}/new.php");
```

The first three lines have been already discussed.
The default `$ux` is `faster-than-quick`. As its name suggest, it is a quick hack that works.

To be completed.

## Case view

To be completed.
