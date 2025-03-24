# SunCAE: Simulations in your browser

![](doc/logo.svg)

> A free and open source web-based platform for performing CAE in the cloud.

## What

SunCAE is a web-based front end and a cloud-based back end to perform finite-element calculations directly on the browser.

 * The front end is an HTML client with plain vanilla javascript code (i.e. no Angular, no React, not even jQuery) that provide interactivity by sending JSON-based AJAX request to the server backe end. 
 * The back end is written in PHP and responds to the client requests by creating the necesary files, executing the necesary binaries and returning back 3D data to the client.

Both fron end and back ends are free software, released under the terms of the [AGPLv3](https://www.gnu.org/licenses/agpl-3.0.en.html). See [licensing](#licensing) below for more information.


## Why

 1. **No need to install.** You can use it [online](https://www.caeplex.com/suncae) because it is web-based.
 2. **Single source of truth.** All your data is in a single location because it is cloud-first.
 3. **Increased traceability.** All changes are tracked using Git.
 4. **Collaboration.** Many users can access the same case. And Git keeps track of who changed what when.
 5. **Mobile-friendly.** Access your simulation project from your phone or tablet.
 6. **Free and open source.** As in "free speech." You fully get the four freedoms.
 7. **Extensible.** Add more meshers, solvers, UXs, etc. Join the community!


## How

You can use SunCAE either by...

 1. using someone else’s servers and configurations

     * open [this link to use SunCAE in our live demo](https://www.caeplex.com/suncae)
     * check out these Youtube videos to learn how to use it
       - [Tutorial #1: Overview](https://youtu.be/MYl7-tcCAfE) (4 min)
       - [Tutorial #2: NAFEMS LE10](https://youtu.be/ANQX0EZI_q8) (4 min)
       - [Tutorial #3: Heat conduction](https://youtu.be/WeEeZ5BVm8I) (3.5 min)


 2. hosting your own server (it can be your laptop!) so you (or other people) can use it:

     1. install some common dependencies
     2. clone the SunCAE repository
     3. run a script to fetch the open source CAE-related tools (renderers, solvers, meshers, etc.):

        ```terminal
        sudo apt-get install git
        git clone https://github.com/seamplex/suncae
        cd suncae
        sudo apt-get install unzip patchelf wget php-cli php-yaml gnuplot
        ./deps.sh
        php -S localhost:8000 -t html
        ```
     4. open <http://localhost:8000> with a web browser

> [!NOTE]
> SunCAE is aimed at the cloud. The cloud likes Unix (and Unix likes the cloud).
> So these instructions apply to Unix-like servers, in particular GNU/Linux.
> There might be ways to run SunCAE on Windows, but we need time to figure out what they are.
>
> Moreover, most CAE solvers do not perform in Windows.
> There is a simple explanation: (good) solvers are written by hackers.
> And hackers---as [Paul Graham already explained more than twenty years ago](https://paulgraham.com/gh.html)---do not like Windows (and Windows do not like hackers either).


For more detailed instructions including setting up production web servers and using virtualization tools (e.g. docker and/or virtual machines) read the [installation guide](doc/INSTALL.md).


### Configuration

With SunCAE---as with sundae ice creams---you get to choose the toppings:

 1. [Authenticators](auths)
     * [single-user](auths/single-user)
     * [htpasswd](auths/htpasswd)
     * ...
 2. [UXs](uxs)
     * [faster-than-quick](uxs/faster-than-quick)
     * ...
 3. [CAD importers](cadimporters)
     * [upload](cadimporters/upload)
     * ...
 4. [CAD processors](cadprocessors)
     * [gmsh](cadprocessors/gmsh)
     * ...
 5. Runners (to be done, e.g. local, ssh, aws, ...)
 6. Post processors (to be done, e.g. paraview, glvis, ...)

Moreover, for each case users can choose the combination of

 * [Meshers](meshers) (e.g. [gmsh](meshers/gmsh, ...)
 * [Solvers](solvers) (e.g. [feenox](solvers/feenox), [calculix](solvers/calculix), ...)


## Licensing

The content of this SunCAE repository is licensed under the terms of the [GNU Affero General Public License version 3](https://www.gnu.org/licenses/agpl-3.0.en.html), or at your option, any later version (AGPLv3+). 

This means that you get the four essential freedoms, so you can

 0. Run SunCAE as you seem fit (i.e. for any purpose).
 1. Investigate the source code to see how SunCAE works and to change it (or to hire someone to change it four you) as you seem fit (i.e. to suit your needs)
 2. Redistribute copies of the source code as you seem fit (i.e. to help your neighbor)
 3. Publish your changes (or the ones that the people you hired made) to the public (i.e. to benefit the community).

> [!IMPORTANT]
> With great power comes great responsibility.

If you use a _modified_ version of SunCAE in your web server, [section 13 of the AGPL license](https://www.gnu.org/licenses/agpl-3.0.en.html#section13) requires you to give a link where your users can get these four freedoms as well.
That is to say, if you use a verbatim copy of SunCAE in your server, there is nothing for you to do (because the link is already provided).
But if you exercise freedoms 1 & 3 above and _modify_ SunCAE to suit your needs---let's say you don't like the button "Add Boundary Condition" and you change it to "Add restrains and loads"---you do need to provide a link for people to download the modified source code.

> [!TIP]
> If this licensing scheme does not suit you, contact us to see how we can make it work.

 * If you have a solver released under a license which is compatible with the AGPL and you would like to add it to SunCAE, feel free to fork the repository (and create a pull request when you are done).
 * If you have a solver released under a license which is not compatible with the AGPL and you would like to add it to SunCAE, contact us.

