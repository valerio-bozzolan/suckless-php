#!/bin/bash
set -e
sed -i 's/ _esc_html(/ echo esc_html(/g'  $@
sed -i 's/ _esc_attr(/ echo esc_attr(/g'  $@
sed -i 's/ _value(/ echo value(/g'        $@
sed -i 's/ _selected(/ echo selected(/g'  $@
sed -i 's/ _checked(/ echo checked(/g'    $@
sed -i 's/ _e(/ echo __(/g'               $@
sed -i 's/ and echo/ and print/g'         $@
sed -i 's/<?php echo/<?=/g'               $@
sed -i 's/<?= echo/ <?=/g'                $@
sed -i 's/get_menu_entry(/menu_entry(/'   $@
sed -i 's/\t_value(/\techo value(/'       $@
sed -i 's/\t_esc_html(/\techo esc_html(/' $@
sed -i 's/\t_selected(/\techo selected(/' $@
sed -i 's/\t_esc_attr(/\t_esc_attr(/'     $@
sed -i 's/\t_checked(/\techo checked(/'   $@
