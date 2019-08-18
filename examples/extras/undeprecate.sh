#!/bin/bash
set -e
sed -i 's/ _esc_html(/ echo esc_html(/g' $@
sed -i 's/ _esc_attr(/ echo esc_attr(/g' $@
sed -i 's/ _value(/ echo value(/g'       $@
sed -i 's/ _selected(/ echo selected(/g' $@
sed -i 's/ _checked(/ echo checked(/g'   $@
sed -i 's/ _e(/ echo __(/g'              $@
sed -i 's/ and echo/ and print/g'        $@
sed -i 's/<?php echo/<?=/g'              $@
sed -i 's/<?= echo/ <?=/g'               $@
