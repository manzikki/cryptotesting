#!/bin/bash
datadir=`dirname "${BASH_SOURCE[0]}"`
nfc=$1
wallet=$2
pw=$3
echo exit |  $datadir/wallet --wallet-file=$datadir/$wallet --password=$pw  --nfc=$nfc
