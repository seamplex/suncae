#!/bin/false

bootstrap_version=5.3.3
bootstrap_icons_version=1.11.3
katex_version=0.16.11
pandoc_version=3.5

# boostrap (we only need the js, the css comes from bootswatch)
echo -n "uxs/faster-than-quick/bootstrap.js... "
bootstrap_tarball=bootstrap-${bootstrap_version}-dist
if [ $force = 1 ] || [ ! -e uxs/faster-than-quick/js/bootstrap.min.js ] || [ ! -f deps/${bootstrap_tarball}.zip ]; then
  cd deps
  if [ ! -e ${bootstrap_tarball}.zip ]; then
    wget https://github.com/twbs/bootstrap/releases/download/v${bootstrap_version}/${bootstrap_tarball}.zip
  fi
  if [ ! -d ${bootstrap_tarball} ]; then
    unzip ${bootstrap_tarball}.zip
  fi
  cp ${bootstrap_tarball}/js/bootstrap.min.js        ../uxs/faster-than-quick/js
  cp ${bootstrap_tarball}/js/bootstrap.bundle.min.js ../uxs/faster-than-quick/js
  echo "done"
  cd .. 
else
  echo "already installed"
fi


# boostrap icons
echo -n "uxs/faster-than-quick/bootstrap icons... "
bootstrap_icons_tarball=bootstrap-icons-${bootstrap_icons_version}

if [ $force = 1 ] || [ ! -e uxs/faster-than-quick/css/bootstrap-icons.min.css ] || [ ! -f deps/${bootstrap_icons_tarball}.zip ]; then
  cd deps
  if [ ! -e ${bootstrap_icons_tarball}.zip ]; then
    wget https://github.com/twbs/icons/releases/download/v${bootstrap_icons_version}/${bootstrap_icons_tarball}.zip
  fi
  if [ ! -d ${bootstrap_icons_tarball} ]; then
    unzip ${bootstrap_icons_tarball}.zip
  fi
  cp ${bootstrap_icons_tarball}/font/bootstrap-icons.min.css     ../uxs/faster-than-quick/css
  mkdir -p ../uxs/faster-than-quick/css/fonts
  cp ${bootstrap_icons_tarball}/font/fonts/bootstrap-icons.woff  ../uxs/faster-than-quick/css/fonts
  cp ${bootstrap_icons_tarball}/font/fonts/bootstrap-icons.woff2 ../uxs/faster-than-quick/css/fonts
  for i in Carlito-Bold Carlito-Italic Carlito-BoldItalic Carlito-Regular; do
    wget https://raw.githubusercontent.com/googlefonts/carlito/refs/heads/main/fonts/ttf/${i}.ttf -O ../uxs/faster-than-quick/css/fonts/${i}.ttf
  done
  echo "done"
  cd .. 
else
  echo "already installed"
fi


# katex
echo -n "uxs/faster-than-quick/katex... "
if [ $force = 1 ] ||  [ ! -e uxs/faster-than-quick/css/katex.min.css ]; then
  cd deps
  if [ ! -e katex.tar.gz ]; then
    wget https://github.com/KaTeX/KaTeX/releases/download/v${katex_version}/katex.tar.gz
  fi
  if [ ! -d katex ]; then
    tar xvzf katex.tar.gz
  fi
  cp katex/katex.min.css     ../uxs/faster-than-quick/css
  mkdir -p ../uxs/faster-than-quick/css/fonts
  cp katex/fonts/* ../uxs/faster-than-quick/css/fonts
  echo "done"
  cd .. 
else
  echo "already installed"
fi

# pandoc
echo -n "uxs/faster-than-quick/pandoc... "
pandoc_tarball=pandoc-${pandoc_version}-linux-amd64
if [ $force = 1 ] || [ ! -e bin/pandoc ] || [ ! -f deps/${pandoc_tarball}.tar.gz ]; then
  cd deps
  if [ ! -e ${pandoc_tarball}.tar.gz ]; then
    wget https://github.com/jgm/pandoc/releases/download/${pandoc_version}/${pandoc_tarball}.tar.gz
  fi
  if [ ! -d pandoc-${pandoc_version} ]; then
    tar xvzf ${pandoc_tarball}.tar.gz
  fi
  cp pandoc-${pandoc_version}/bin/pandoc        ../bin
  echo "done"
  cd .. 
else
  echo "already installed"
fi

# TODO: gnuplot?

