#!/bin/bash
sed -i 's/ _esc_html(/ echo esc_html(/' $@
sed -i 's/ _esc_attr(/ echo esc_attr(/' $@
sed -i 's/ _value(/ echo value(/'       $@
sed -i 's/ _selected(/ echo selected(/' $@
sed -i 's/ _checked(/ echo checked(/'   $@
sed -i 's/ _e(/ echo __(/'              $@
sed -i 's/<?php echo/<?=/'              $@
