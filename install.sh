#!/usr/bin/env bash
DEST_PATH=~/.ypi

if [ "$1" == "global" ]; then
    DEST_PATH=/usr/local/ypi
fi;

if mkdir -p $DEST_PATH && cd $DEST_PATH && git clone git://github.com/yonpols/ypinfrastructure.git; then
    echo 'export PATH=$PATH:$DEST_PATH/bin' >> ~/.bashrc
    source ~/.bashrc
    echo "YPInfrastructure installed correctly"
    echo "Please add '$DEST_PATH' to your php include path"
fi