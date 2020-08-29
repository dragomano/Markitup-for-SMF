<?php

/**
 * Class-MarkItUp.php
 *
 * @package markItUp! for SMF
 * @link https://custom.simplemachines.org/mods/index.php?mod=3246
 * @author Bugo https://dragomano.ru/mods/markitup-for-smf
 * @copyright 2011-2020 Bugo
 * @license https://opensource.org/licenses/MIT MIT
 *
 * @version 0.7.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class MarkItUp
{
	/**
	 * Подключаем необходимые хуки
	 *
	 * @return void
	 */
	public static function hooks()
	{
		add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme', false);
		add_integration_function('integrate_load_permissions', __CLASS__ . '::loadPermissions', false);
		add_integration_function('integrate_general_mod_settings', __CLASS__ . '::generalModSettings', false);
	}

	/**
	 * Подключаем используемые стили и скрипты
	 *
	 * @return void
	 */
	public static function loadTheme()
	{
		global $modSettings, $context, $options, $txt, $settings, $sourcedir, $smcFunc;

		loadLanguage('markItUp');

		if (!allowedTo('use_markItUp') || (defined('WIRELESS') && WIRELESS) || empty($modSettings['enableBBC']) || $context['current_action'] == 'printpage')
			return;

		$areas = empty($modSettings['markItUp_areas']) ? array() : explode(",", str_replace(" ", "", $modSettings['markItUp_areas']));

		if ((!empty($options['display_quick_reply']) && (!empty($_REQUEST['topic']) && empty($context['current_action'])) || in_array($context['current_action'], $areas)))	{
			loadLanguage('Post');
			$modSettings['disable_wysiwyg'] = true;

			$disabled = array();
			if (!empty($modSettings['disabledBBC'])) {
				foreach (explode(",", $modSettings['disabledBBC']) as $tag)
					$disabled[$tag] = true;
			}

			if (!$context['browser']['is_opera'])
				$txt['quick_reply_desc'] .= $txt['quick_reply_desc_add'];

			$skin = empty($modSettings['markItUp_skin']) ? 'jtageditor' : $modSettings['markItUp_skin'];

			$context['html_headers'] .= '
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/scripts/markitup/skins/' . $skin . '/style' . ($context['right_to_left'] ? '-rtl' : '') . '.css" />
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/scripts/markitup/sets/bbcode/style' . ($context['right_to_left'] ? '-rtl' : '') . '.css" />';

			$context['insert_after_template'] .= '
	<script type="text/javascript">window.jQuery || document.write(unescape(\'%3Cscript src="//code.jquery.com/jquery.min.js"%3E%3C/script%3E\'))</script>
	<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/markitup/jquery.markitup.js"></script>
	<script type="text/javascript"><!-- // --><![CDATA[
		mySettings = {
			resizeHandle: false,
			onTab: {keepDefault:false, replaceWith:"    "},
			markupSet: [
				' . (!isset($disabled['b']) ? '{name:"' . $txt['bold'] . '", key:"B", openWith:"[b]", closeWith:"[/b]", className:"bold"},' : '') . '
				' . (!isset($disabled['i']) ? '{name:"' . $txt['italic'] . '", key:"I", openWith:"[i]", closeWith:"[/i]", className:"italic"},' : '') . '
				' . (!isset($disabled['u']) ? '{name:"' . $txt['underline'] . '", key:"U", openWith:"[u]", closeWith:"[/u]", className:"underline"},' : '') . '
				' . (!isset($disabled['s']) ? '{name:"' . $txt['strike'] . '", openWith:"[s]", closeWith:"[/s]", className:"strike"},' : '') . '
				{separator:\'---------------\' },
				' . (!isset($disabled['left']) ? '{name:"' . $txt['left_align'] . '", openWith:"[left]", closeWith:"[/left]", className:"left"},' : '') . '
				' . (!isset($disabled['center']) ? '{name:"' . $txt['center'] . '", openWith:"[center]", closeWith:"[/center]", className:"center"},' : '') . '
				' . (!isset($disabled['right']) ? '{name:"' . $txt['right_align'] . '", openWith:"[right]", closeWith:"[/right]", className:"right"},' : '') . '
				' . (!isset($disabled['justify']) && isset($txt['justify']) ? '{name:"' . $txt['justify'] . '", openWith:"[justify]", closeWith:"[/justify]", className:"justify"},' : '') . '
				{separator:\'---------------\' },
				' . (!isset($disabled['img']) ? '{name:"' . $txt['image'] . '", key:"P", openWith:"[img(!( alt=[![' . $txt['image_desc'] . ']!])!)][![' . $txt['prompt_text_img'] . ']!][/img]", className:"picture"},' : '') . '
				' . (!isset($disabled['url']) ? '{name:"' . $txt['hyperlink'] . '", key:"L", openWith:"[url=[![' . $txt['prompt_text_url'] . ']!]][![' . $txt['hyperlink_text'] . ']!][/url]", closeWith:"", className:"link"},' : '');

			// Uppod Player mod is installed?
			if (file_exists($sourcedir . '/Subs-Uppod.php')) {
				loadLanguage('Uppod');

				$context['insert_after_template'] .= '
				{separator:\'---------------\' },
				{name:"' . $txt['uppod_video'] . '", openWith:"[video]", closeWith:"[/video]", className:"video",
					dropMenu: [
						{name:"' . $txt['uppod_audio'] . '", openWith:"[audio]", closeWith:"[/audio]", className:"audio"},
						{name:"' . $txt['uppod_photo'] . '", openWith:"[photo]", closeWith:"[/photo]", className:"photo"},
						{name:"' . $txt['uppod_plvideo'] . '", className:"plvideo",
							replaceWith:function(markItUp) {
								out = \'[plvideo]{"playlist":[\n\';
								out += \'{"comment":"Sample Video 1","file":"sample_1.flv","poster":"sample_1_tmb.jpg","bigposter":"sample_1_big.jpg","sub":"sample_1_sub.ass"},\n\';
								out += \'{"comment":"Sample Video 2","file":"sample_2.flv","poster":"sample_2_tmb.jpg","bigposter":"sample_2_big.jpg","sub":"sample_2_sub.ass"}]}[/plvideo]\';
								return out;
							}
						},
						{name:"' . $txt['uppod_plaudio'] . '", className:"plaudio",
							replaceWith:function(markItUp) {
								out = \'[plaudio]{"playlist":[\n\';
								out += \'{"comment":"Sample Audio 1","file":"sample_1.mp3"},\n\';
								out += \'{"comment":"Sample Audio 2","file":"sample_2.mp3"}]}[/plaudio]\';
								return out;
							}
						},
						{name:"' . $txt['uppod_plphoto'] . '", className:"plphoto",
							replaceWith:function(markItUp) {
								out = \'[plphoto]{"playlist":[\n\';
								out += \'{"comment":"Sample Image 1","file":"big_sample_1.png","poster":"small_sample_1.png"},\n\';
								out += \'{"comment":"Sample Image 2","file":"big_sample_2.png","poster":"small_sample_2.png"}]}[/plphoto]\';
								return out;
							}
						}
					]
				},';
			}

			if (!isset($disabled['font']))
				$context['insert_after_template'] .= '
				{separator:\'---------------\' },
				{name:"' . $txt['font_face'] . '", openWith:"[font=[![' . $txt['font_face'] . ']!]]", closeWith:"[/font]", className:"face",
					dropMenu: [
						{name:"Courier", openWith:"[font=courier]", closeWith:"[/font]", className:"face-courier"},
						{name:"Arial", openWith:"[font=arial]", closeWith:"[/font]", className:"face-arial"},
						{name:"Arial Black", openWith:"[font=arial black]", closeWith:"[/font]", className:"face-arial-black"},
						{name:"Impact", openWith:"[font=impact]", closeWith:"[/font]", className:"face-impact"},
						{name:"Verdana", openWith:"[font=verdana]", closeWith:"[/font]", className:"face-verdana"},
						{name:"Times New Roman", openWith:"[font=times new roman]", closeWith:"[/font]", className:"face-times"},
						{name:"Georgia", openWith:"[font=georgia]", closeWith:"[/font]", className:"face-georgia"},
						{name:"Andale Mono", openWith:"[font=andale mono]", closeWith:"[/font]", className:"face-andale-mono"},
						{name:"Trebuchet MS", openWith:"[font=trebuchet ms]", closeWith:"[/font]", className:"face-trebuchet"},
						{name:"Comic Sans MS", openWith:"[font=comic sans ms]", closeWith:"[/font]", className:"face-comic-sans"}
					]
				},';

			if (!isset($disabled['size']))
				$context['insert_after_template'] .= '
				{name:"' . $txt['font_size'] . '", key:"S", openWith:"[size=[![' . $txt['font_size'] . ']!]pt]", closeWith:"[/size]", className:"font",
					dropMenu: [
						{name:"8pt", openWith:"[size=8pt]", closeWith:"[/size]", className:"font-8"},
						{name:"10pt", openWith:"[size=10pt]", closeWith:"[/size]", className:"font-10"},
						{name:"12pt", openWith:"[size=12pt]", closeWith:"[/size]", className:"font-12"},
						{name:"14pt", openWith:"[size=14pt]", closeWith:"[/size]", className:"font-14"},
						{name:"16pt", openWith:"[size=16pt]", closeWith:"[/size]", className:"font-16"},
						{name:"18pt", openWith:"[size=18pt]", closeWith:"[/size]", className:"font-18"},
						{name:"24pt", openWith:"[size=24pt]", closeWith:"[/size]", className:"font-24"},
						{name:"36pt", openWith:"[size=36pt]", closeWith:"[/size]", className:"font-36"}
					]
				},';

			if (!isset($disabled['color']))
				$context['insert_after_template'] .= '
				{name:"' . $txt['change_color'] . '", openWith:"[color=[![' . $txt['change_color'] . ']!]]", closeWith:"[/color]", className:"colors",
					dropMenu: [
						{name:"' . $txt['yellow'] . '",	openWith:"[color=#ff0]", closeWith:"[/color]", className:"col1-1"},
						{name:"' . $txt['orange'] . '", openWith:"[color=orange]", closeWith:"[/color]", className:"col1-2"},
						{name:"' . $txt['red'] . '", openWith:"[color=red]", closeWith:"[/color]", className:"col1-3"},
						{name:"' . $txt['lime_green'] . '",	openWith:"[color=#32CD32]", closeWith:"[/color]", className:"col1-4"},
						{name:"' . $txt['blue'] . '", openWith:"[color=blue]", 	closeWith:"[/color]", className:"col2-1"},
						{name:"' . $txt['purple'] . '", openWith:"[color=purple]", closeWith:"[/color]", className:"col2-2"},
						{name:"' . $txt['green'] . '", openWith:"[color=green]", closeWith:"[/color]", className:"col2-3"},
						{name:"' . $txt['pink'] . '", openWith:"[color=#FFC0CB]", closeWith:"[/color]", className:"col2-4"},
						{name:"' . $txt['white'] . '", openWith:"[color=#fff]", closeWith:"[/color]", className:"col3-1"},
						{name:"' . $txt['teal'] . '", openWith:"[color=teal]", closeWith:"[/color]", className:"col3-2"},
						{name:"' . $txt['black'] . '", openWith:"[color=#000]", closeWith:"[/color]", className:"col3-3"},
						{name:"' . $txt['beige'] . '", openWith:"[color=#F5F5DC]", closeWith:"[/color]", className:"col3-4"},
						{name:"' . $txt['maroon'] . '", openWith:"[color=maroon]", closeWith:"[/color]", className:"col4-1"},
						{name:"' . $txt['brown'] . '", openWith:"[color=#A52A2A]", closeWith:"[/color]", className:"col4-2"},
						{name:"' . $txt['navy'] . '", openWith:"[color=navy]", closeWith:"[/color]", className:"col4-3"},
						{name:"' . $txt['yellow_green'] . '", openWith:"[color=#9ACD32]",	closeWith:"[/color]", className:"col4-4"}
					]
				},';

			if (!isset($disabled['list']))
				$context['insert_after_template'] .= '
				{separator:\'---------------\' },
				{name:"' . $txt['list_unordered'] . '", openWith:"[list]\n[li]", closeWith:"[/li]\n[li][/li]\n[li][/li]\n[/list]", className:"list-bullet"},
				{name:"' . $txt['list_ordered'] . '", openWith:"[list type=decimal]\n[li]", closeWith:"[/li]\n[li][/li]\n[li][/li]\n[/list]", className:"list-numeric",
					dropMenu: [
						{name:"none", openWith:"[list type=none]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"disc", openWith:"[list type=disc]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"circle", openWith:"[list type=circle]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"square", openWith:"[list type=square]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"decimal", openWith:"[list type=decimal]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"decimal-leading-zero", openWith:"[list type=decimal-leading-zero]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"lower-roman", openWith:"[list type=lower-roman]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"upper-roman", openWith:"[list type=upper-roman]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"lower-alpha", openWith:"[list type=lower-alpha]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"upper-alpha", openWith:"[list type=upper-alpha]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"lower-greek", openWith:"[list type=lower-greek]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"lower-latin", openWith:"[list type=lower-latin]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"upper-latin", openWith:"[list type=upper-latin]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"hebrew", openWith:"[list type=hebrew]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"armenian", openWith:"[list type=armenian]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"georgian", openWith:"[list type=georgian]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"cjk-ideographic", openWith:"[list type=cjk-ideographic]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"hiragana", openWith:"[list type=hiragana]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"katakana", openWith:"[list type=katakana]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"hiragana-iroha", openWith:"[list type=hiragana-iroha]\n[li]", closeWith:"[/li]\n[/list]"},
						{name:"katakana-iroha", openWith:"[list type=katakana-iroha]\n[li]", closeWith:"[/li]\n[/list]"}
					]
				},';

			if (!isset($disabled['li']))
				$context['insert_after_template'] .= '
				{name:"' . $txt['list_item'] . '", openWith:"[li]", closeWith:"[/li]", className:"list-item"},';

			if (!isset($disabled['table']) && !isset($disabled['tr']) && !isset($disabled['td']))
				$context['insert_after_template'] .= '
				{separator:\'---------------\' },
				{name:"' . $txt['table'] . '",
					openWith:"[table]",
					closeWith:"[/table]",
					placeHolder:"[tr][td][/td][/tr]",
					className:"table"
				},
				{name:"' . $txt["table_tr"] . '",
					openWith:"[tr]",
					closeWith:"[/tr]",
					placeHolder:"[td][/td]",
					className:"table-col"
				},
				{name:"' . $txt["table_td"] . '",
					openWith:"[td]",
					closeWith:"[/td]",
					className:"table-row"
				},
				{name:"' . $txt['table_generator'] . '",
					className:"tablegenerator",
					placeholder:"' . $txt['table_text'] . '",
					replaceWith:function(markItUp) {
						var cols = prompt("' . $txt['table_question_cols'] . '");
						if (cols == null) return false;
						var	rows = prompt("' . $txt['table_question_rows'] . '");
						if (rows == null) return false;
						out = "[table]\n";
						for (var r = 0; r < rows; r++) {
							out += "[tr]\n";
							for (var c = 0; c < cols; c++) {
								out += "[td]"+(markItUp.placeholder||"")+"[/td]\n";
							}
							out += "[/tr]\n";
						}
						out += "[/table]\n";
						return out;
					}
				},
				{separator:\'---------------\' },';

			if (!isset($disabled['quote']))
				$context['insert_after_template'] .= '
				{name:"' . $txt['bbc_quote'] . '", openWith:"[quote]", closeWith:"[/quote]", className:"quote_btn"},';

			if (!isset($disabled['code']))
				$context['insert_after_template'] .= '
				{name:"' . $txt['bbc_code'] . '", openWith:"[code]", closeWith:"[/code]", className:"code"},';

			// Quick Spoiler mod is installed?
			if (method_exists('QuickSpoiler', 'bbcCodes')) {
				loadLanguage('QuickSpoiler/');

				if (!isset($disabled['spoiler']))
					$context['insert_after_template'] .= '
				{name:"' . $txt['quick_spoiler'] . '", openWith:"[spoiler(!(=[![' . $txt['title'] . ']!])!)]", closeWith:"[/spoiler]", className:"spoiler"},';
			}

			// Hide Tag Special mod is installed?
			if (file_exists($sourcedir . '/HtsAdmin.php') && !isset($disabled['hide']))	{
				$context['insert_after_template'] .= '
				{name:"' . $txt['hts_bbc'] . '", openWith:"[hide]", closeWith:"[/hide]", className:"hide"},';
			}

			// MathJax mod is installed?
			if (file_exists($sourcedir . '/Mathjax.php') && !isset($disabled['latex']))	{
				$context['insert_after_template'] .= '
				{name:"' . $txt['latex'] . '", openWith:"[latex(!(=inline)!)]", closeWith:"[/latex]", className:"latex"},';
			}

			// Intermission
			$context['insert_after_template'] .= '
				{separator:\'---------------\' },
				{name:"' . $txt['current_day'] . '", replaceWith:"' . timeformat(time(), false) . '", className:"dateoftheday"},
				{name:"' . $txt['alphabetic_sort'] . '",
					className:"sort",
					replaceWith:function(h) {
						var s = h.selection.split((($.browser.mozilla) ? "\n" : "\r\n"));
						s.sort();
						if (h.altKey) s.reverse();
						return s.join("\n");
					}
				},
				{separator:\'---------------\' },
				' . ($context['user']['is_admin'] ? '{name:"' . $txt['html'] . '", openWith:"[html]", closeWith:"[/html]", className:"html"},' : '') . '
				{name:"' . $txt['text_replace'] . '", className:"replace",
					beforeInsert:function(markItUp) {
						str = markItUp.textarea.value;
						var s_search = prompt("' . $txt['text_replace_what'] . '");
						if (s_search == null) return false;
						var s_replace = prompt("' . $txt['text_replace_with'] . '");
						if (s_replace == null) return false;
						markItUp.textarea.value = str.replace(new RegExp(s_search,"g"), s_replace);
						alert("' . $txt['text_replace_done'] . '");
					}
				},
				{name:"' . $txt['clean'] . '", className:"clean", replaceWith:function(markitup) {return markitup.selection.replace(/\[(.*?)\]/g, "")}}
			]
		};
		jQuery(document).ready(function($) {
			$("form textarea").markItUp(mySettings);
			$(".quickReplyContent").removeClass();
			$("#bbcBox_message,#smileyBox_message,#message_resizer").hide();
			$(".markItUpEditor").before(\'<div id="emoticons" class="floatleft">\' +';

			// Smileys
			if (empty($modSettings['markItUp_disable_smileys']) && ($context['markitup']['smileys'] = cache_get_data('markitup_smileys', 3600)) == null) {
				$context['markitup']['smileys'] = '';
				$smileys = array();
				$smileys_dir = $modSettings['smileys_url'] . '/' . $modSettings['smiley_sets_default'] . '/';

				$request = $smcFunc['db_query']('', '
					SELECT code, filename, description
					FROM {db_prefix}smileys
					WHERE hidden = {int:type}
					ORDER BY id_smiley',
					array(
						'type' => 0
					)
				);

				while ($row = $smcFunc['db_fetch_assoc']($request))
					$smileys[] = $row;

				$smcFunc['db_free_result']($request);

				foreach ($smileys as $sm) {
					$path = $smileys_dir . $sm['filename'];
					$context['markitup']['smileys'] .= '
					\'<img src="' . addslashes($path) . '" alt="' . addslashes($sm['code']) . '" title="' . addslashes($sm['description']) . '" style="cursor: pointer" />&nbsp;\' +';
				}

				cache_put_data('markitup_smileys', $context['markitup']['smileys'], 3600);
			}

			if (empty($modSettings['markItUp_disable_smileys']))
				$context['insert_after_template'] .= $context['markitup']['smileys'];

			$context['insert_after_template'] .= '
			\'</div>\');
			$(".righttext").removeClass("padding");
			$("#emoticons").on("click", "img", function() {
				emoticon = $(this).attr("alt");
				$("form textarea").focus();
				$.markItUp({
					replaceWith:emoticon
				});
			});
		});
	// ]]></script>';
		}
	}

	/**
	 * Права доступа для использования редактора
	 *
	 * @param array $permissionGroups
	 * @param array $permissionList
	 * @return void
	 */
	public static function loadPermissions(&$permissionGroups, &$permissionList)
	{
		$permissionList['membergroup']['use_markItUp'] = array(false, 'general', 'view_basic_info');
	}

	/**
	 * Опции мода на странице общих настроек модификаций
	 *
	 * @param array $config_vars
	 * @return void
	 */
	public static function generalModSettings(&$config_vars)
	{
		if (isset($config_vars[0]))
			$config_vars[] = array('title', 'markItUp_settings');

		$config_vars[] = array('select', 'markItUp_skin', array(0 => 'jtageditor', 'markitup' => 'markitup', 'simple' => 'simple'));
		$config_vars[] = array('check', 'markItUp_disable_smileys');
		$config_vars[] = array('text', 'markItUp_areas', 60);
		$config_vars[] = array('permissions', 'use_markItUp');
	}
}
