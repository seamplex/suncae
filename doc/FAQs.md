# Frequently asked questions

> [!TIP]
> If your question is not listed here, do not hesitate [contacting us](https://www.seamplex.com/suncae/#contact).

### What is the difference between SunCAE, FeenoX, Seamplex and CAEplex?

 * [SunCAE](https://www.seamplex.com/suncae) is an open-source web-based interface for performing CAE in the cloud.
 * [FeenoX](https://www.seamplex.com/feenox) is an open-source finite-element solver which used in SunCAE by default.
 * [Seamplex](https://www.seamplex.com) is the company that developed both SunCAE and FeenoX
 * [CAEplex](https://www.caeplex.com) is the first web-based interface developed by Seamplex. It is [100% integrated into Onshape](https://www.youtube.com/watch?v=ylXAUAsfb5E).


### Can I try SunCAE without having to install it?

Yes, check out our [live demo](https://www.caeplex.com/suncae).
Note that

 * There is no need to register, but your IP might get logged.
 * The data (including CAD files) might (or not) get lost. Do not put sensitive stuff in the demo.

### Which language is SunCAE written in?

The front end is HTML with plain vanilla Javascript. This means no Angular, no React, no NodeJS, nothing. Not even JQuery. Plain vanilla Javascript (plus the Javascript libraries needed for 3D rendering).

The back end is written in PHP. Again, plain PHP with at most `php-yaml` to read and write YAML files.
The front end would make asynchronous AJAX calls to the back end, which would run PHP file and respond with a JSON content and so the front end can update the DOM. 
 
### What are the licensing terms?

TL;DR: if you use a modified version of SunCAE in your server, you have to provide a link to the modified sources.

The content of this SunCAE repository is licensed under the terms of the [GNU Affero General Public License version 3](https://www.gnu.org/licenses/agpl-3.0.en.html), or at your option, any later version (AGPLv3+). 

See the [licenses table](../LICENSES.md) and [licensing details](../README.,d#licensing) for more information.


### How does SunCAE import the CAD geometry?

So far, only using [OpenCASCADE](https://dev.opencascade.org) through the [Gmsh API](https://gitlab.onelab.info/gmsh/gmsh/-/tree/master/api).

> [!NOTE]
> If you want other CAD imported to be supported, say so in the [forum](https://github.com/seamplex/suncae/discussions).

### What are the supported meshers?

So far, only [Gmsh](http://gmsh.info/) is supported.

See the directory [`meshers`](https://github.com/seamplex/suncae/tree/main/meshers) for the current list.

> [!NOTE]
> If you want other meshers to be supported, say so in the [forum](https://github.com/seamplex/suncae/discussions).


### What are the supported solvers?

 * [FeenoX](http://www.seamplex.com/feenox)
 * [CalculiX](https://www.dhondt.de/)
 
The "single source of truth" is still the FeenoX input file. 
CalculiX is supportted through the [`fee2ccx` converter](https://github.com/seamplex/feenox/tree/main/utils/fee2ccx) from `.fee` to `.inp`. 

See the directory [`solvers`](https://github.com/seamplex/suncae/tree/main/solvers) for the current list.

> [!NOTE]
> If you want other solvers to be supported, say so in the [forum](https://github.com/seamplex/suncae/discussions).

