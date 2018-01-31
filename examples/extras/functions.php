<?php
# Copyright (C) 2015 Valerio Bozzolan
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Some useful functions to DO IT YOURSELF
 */

/**
 * Generate a menu tree!
 * A recursive function to generate a menu tree.
 *
 * @param string $uid The menu identifier
 * @param int $level Level of the menu, used internally. Default 0.
 * @param array $args Arguments
 */
function print_menu($uid = null, $level = 0, $args = [] ) {

	// Example of custom default args
	$args = merge_args_defaults( $args, [
		'max-level' => 99,
		'menu-ul-intag' => 'class="collection"'
	] );

	// End menu if level reached
	if( $level > $args['max-level'] ) {
		return;
	}

	$menuEntries = get_children_menu_entries($uid);

	if( ! $menuEntries ) {
		return;
	}
	?>
		<ul<?php if($level === 0): echo " {$args['menu-ul-intag']}"; endif ?>>
		<?php foreach($menuEntries as $menuEntry): ?>
			<li class="collection-item">
				<?php echo HTML::a($menuEntry->url, $menuEntry->name, $menuEntry->get('title')) ?>
				<?php print_menu( $menuEntry->uid, $level + 1, $args ) ?>
			</li>
		<?php endforeach ?>
		</ul>
	<?php
}

/**
 * Print a single menu link.
 * @param string $uid Menu identifier
 * @param string $text Override link text
 * @param string $classes class="$classes"
 * @param string $intag e.g. 'target="_blank"'
 */
function print_menu_link($uid, $text = null, $classes = null, $intag = null) {

	if( ! $menu  = get_menu_entry($uid) ) {
		return null;
	}

	echo HTML::a(
		$menu->url,
		($text) ? $text : $menu->name,
		$menu->get('title', $menu->name),
		$classes,
		$intag
	);
}
