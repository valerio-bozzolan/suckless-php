#!/bin/bash
sed -i 's/_esc_html(/echo esc_html(/' *.{html,php}
sed -i 's/_esc_attr(/echo esc_attr(/' *.{html,php}
sed -i 's/_value(/echo value(/'       *.{html,php}
sed -i 's/_selected(/echo selected(/' *.{html,php}
sed -i 's/_checked(/echo checked(/'   *.{html,php}
sed -i 's/<?php echo/<?=/'            *.{html,php}