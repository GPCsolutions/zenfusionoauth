<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2013 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013 Cédric Salvador    <csalvador@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Loads scripts for copy to clipboard
 * @return string HTML scripts
 */
function zfInitCopyToClipboardButton()
{
    $zeroclipboard_path = dol_buildpath('/zenfusionoauth/lib/zeroclipboard/dist/', 2);

    return '
        <script type="text/javascript" src="' . $zeroclipboard_path . 'ZeroClipboard.js"></script>
        <script type="text/javascript">
            ZeroClipboard.config( {
                swfPath: "' . $zeroclipboard_path . 'ZeroClipboard.swf"
            } );
        </script>';
}

/**
 * Button to copy text to clipboard
 *
 * @param string $text The text to copy
 * @param string $id Id of the element
 * @param string $title Title of the element
 *
 * @return string HTML for the button
 */
function zfCopyToClipboardButton($text, $id = 'copy-button', $title = 'CopyToClipboard')
{
    global $langs;

    return '
        <button
            type="button"
            class="button"
            id="' . $id . '"
            data-clipboard-text="' . $text . '">
        <img src="' . dol_buildpath('/zenfusionoauth/img/', 2) . 'copy.png">'
        . '&nbsp;' . $langs->trans($title)
        . '</button>
        <script type="text/javascript">
            var client'.$id.' = new ZeroClipboard( document.getElementById("' . $id . '") );

            client'.$id.'.on( "ready", function( event ) {
                client'.$id.'.on( "aftercopy", function( event ) {
                    $.jnotify(
                        \'' . $langs->trans('CopiedToClipboard') . '\',
                        \'3000\',
                        \'true\'
                    );
                } );
            } );
        </script>';
}
