<?php namespace ProcessWire;
// This is a customised version of https://github.com/DaveChild/Text-Statistics
// I've added some fixes marked "RPS"
use DaveChild\TextStatistics as TS;

class TextReadability extends WireData implements Module, ConfigurableModule {

	protected $info = [];

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct();
		require_once __DIR__ . '/vendor/autoload.php';
		$this->info = [
			'fleschKincaidReadingEase' => [
				'label' => $this->_('Flesch Kincaid Reading Ease'),
				'url' => 'https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/',
			],
			'fleschKincaidGradeLevel' => [
				'label' => $this->_('Flesch Kincaid Grade Level'),
				'url' => 'https://readable.com/readability/flesch-reading-ease-flesch-kincaid-grade-level/',
			],
			'gunningFogScore' => [
				'label' => $this->_('Gunning Fog Index'),
				'url' => 'https://readable.com/readability/gunning-fog-index/',
			],
			'smogIndex' => [
				'label' => $this->_('SMOG Index'),
				'url' => 'https://readable.com/readability/smog-index/',
			],
			'automatedReadabilityIndex' => [
				'label' => $this->_('Automated Reability Index'),
				'url' => 'https://readable.com/readability/automated-readability-index/',
			],
			'spacheReadabilityScore' => [
				'label' => $this->_('Spache Readability Score'),
				'url' => 'https://readable.com/readability/spache-readability-formula/',
			],
			'daleChallReadabilityScore' => [
				'label' => $this->_('Dale Chall Readability Score'),
				'url' => 'https://readable.com/readability/new-dale-chall-readability-formula/',
			],
			'colemanLiauIndex' => [
				'label' => $this->_('Coleman Liau Index'),
				'url' => 'https://readable.com/readability/coleman-liau-readability-index/',
			],
		];
		$this->enabledTests = array_keys($this->info);
		$this->showResults = 'click';
	}

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHookBefore('InputfieldTextarea::renderReadyHook', $this, 'beforeTextareaRenderReady');
	}

	/**
	 * Before InputfieldTextarea::renderReadyHook
	 *
	 * @param HookEvent $event
	 */
	protected function beforeTextareaRenderReady(HookEvent $event) {
		/** @var InputfieldTextarea $inputfield */
		$inputfield = $event->object;
		$config = $this->wire()->config;
		$field = $inputfield->hasField;
		$page = $inputfield->hasPage;
		// Return early if this inputfield is not associated with a Field and a Page
		if(!$field || !$page) return;
		// Return early if disallowed according to hookable method
		if(!$this->allowReadabilityResults($field, $page)) return;

		// Get the (English) text via the formatted value to include the results of textformatters like Hanna Code
		$of = $page->of();
		$page->of(true);
		if($inputfield->useLanguages) {
			$english = $this->englishLanguage ?: $this->getEnglishLanguage();
			$text = $page->getLanguageValue($english, $field->name);
		} else {
			$text = $page->get($field->name);
		}
		$page->of($of);
		$text = strip_tags($text);

		// Return early if there is no text
		if(!$text) return;

		// Add assets
		$info = $this->wire()->modules->getModuleInfo($this);
		$version = $info['version'];
		$config->scripts->add($config->urls->$this . "$this.js?v=$version");
		$config->styles->add($config->urls->$this . "$this.css?v=$version");

		// Add class or header action depending on the showResults setting
		if($this->showResults === 'always') {
			$inputfield->wrapClass('tr-show-readability-results');
		} else {
			$inputfield->addHeaderAction([
				'icon' => 'book',
				'tooltip' => $this->_('Show/hide readability results'),
				'event' => 'trResultsToggle',
			]);
		}

		// Generate and append readability results
		$statistics = new TS\TextStatistics;
		$append = '<div class="tr-readability-results">';
		foreach($this->enabledTests as $name) {
			$result = $statistics->$name($text);
			$tooltip = $this->getResultTooltip($result, $name);
			$append .= "<div><a href='{$this->info[$name]['url']}' target='_blank'>{$this->info[$name]['label']}:</a> <b uk-tooltip='$tooltip'>$result</b></div>";
		}
		$append .= '</div>';
		$inputfield->appendMarkup($append);
	}

	/**
	 * Get an interpretive tooltip for a readability result
	 *
	 * @param float $result
	 * @param string $test
	 * @return string
	 */
	public function ___getResultTooltip($result, $test) {

		// https://en.wikipedia.org/wiki/Flesch%E2%80%93Kincaid_readability_tests#Flesch_reading_ease
		$flesch_reading_ease = [
			[
				'min' => 0,
				'max' => 10,
				'desc' => $this->_('Level: Professional. Extremely difficult to read. Best understood by university graduates.'),
			],
			[
				'min' => 10,
				'max' => 30,
				'desc' => $this->_('Level: College graduate. Very difficult to read. Best understood by university graduates.'),
			],
			[
				'min' => 30,
				'max' => 50,
				'desc' => $this->_('Level: College. Difficult to read.'),
			],
			[
				'min' => 50,
				'max' => 60,
				'desc' => $this->_('Level: 10th to 12th grade. Fairly difficult to read.'),
			],
			[
				'min' => 60,
				'max' => 70,
				'desc' => $this->_('Level: 8th & 9th grade. Plain English. Easily understood by 13- to 15-year-old students.'),
			],
			[
				'min' => 70,
				'max' => 80,
				'desc' => $this->_('Level: 7th grade. Fairly easy to read.'),
			],
			[
				'min' => 80,
				'max' => 90,
				'desc' => $this->_('Level: 6th grade. Easy to read. Conversational English for consumers.'),
			],
			[
				'min' => 90,
				'max' => 10000,
				'desc' => $this->_('Level: 5th grade. Very easy to read. Easily understood by an average 11-year-old student.'),
			],
		];

		// https://originality.ai/blog/new-dale-chall-readability-formula
		$dale_chall = [
			[
				'min' => 0,
				'max' => 5,
				'desc' => $this->_('Level: 4th grade and below. Very easy to read.'),
			],
			[
				'min' => 5,
				'max' => 6,
				'desc' => $this->_('Level: 5th & 6th grade. Easy to read.'),
			],
			[
				'min' => 6,
				'max' => 7,
				'desc' => $this->_('Level: 7th & 8th grade. Conversational English.'),
			],
			[
				'min' => 7,
				'max' => 8,
				'desc' => $this->_('Level: 9th & 10th grade. Conversational English.'),
			],
			[
				'min' => 8,
				'max' => 9,
				'desc' => $this->_('Level: 11th & 12th grade. Quite hard to read.'),
			],
			[
				'min' => 9,
				'max' => 10,
				'desc' => $this->_('Level: College. Difficult to read.'),
			],
			[
				'min' => 10,
				'max' => 10000,
				'desc' => $this->_('Level: College graduates. Very difficult to read.'),
			],
		];

		$grades = [
			0 => $this->_('Kindergarten, ages 5-6'),
			1 => $this->_('1st grade, ages 6-7'),
			2 => $this->_('2nd grade, ages 7-8'),
			3 => $this->_('3rd grade, ages 8-9'),
			4 => $this->_('4th grade, ages 9-10'),
			5 => $this->_('5th grade, ages 10-11'),
			6 => $this->_('6th grade, ages 11-12'),
			7 => $this->_('7th grade, ages 12-13'),
			8 => $this->_('8th grade, ages 13-14'),
			9 => $this->_('9th grade (freshman), ages 14-15'),
			10 => $this->_('10th grade (sophomore), ages 15-16'),
			11 => $this->_('11th grade (junior), ages 16-17'),
			12 => $this->_('12th grade (senior), ages 17-18'),
			13 => $this->_('College freshman, ages 18-19'),
			14 => $this->_('College sophomore, ages 19-20'),
			15 => $this->_('College junior, ages 20-21'),
			16 => $this->_('College senior, ages 21-22'),
			17 => $this->_('Graduate level, ages 22+'),
			18 => $this->_('Graduate level, ages 22+'),
			19 => $this->_('Professional level, ages 22+'),
			20 => $this->_('Professional level, ages 22+'),
		];

		switch($test) {
			case 'fleschKincaidReadingEase':
				$tooltip = $this->findRangeDescription($result, $flesch_reading_ease);
				break;
			case 'daleChallReadabilityScore':
				$tooltip = $this->findRangeDescription($result, $dale_chall);
				break;
			default:
				$rounded_result = round($result);
				if($rounded_result > 20) $rounded_result = 20;
				$tooltip = $grades[$rounded_result];
				break;
		}
		if($test === 'spacheReadabilityScore') {
			$tooltip .= '. ' . $this->_('This score is only intended for texts that are for children up to 4th grade.');
		}
		// Fix for issue where Uikit tooltip contains a colon character
		// https://github.com/uikit/uikit/issues/3390
		if($tooltip) $tooltip = 'title: ' . $tooltip;
		return $tooltip;
	}


	/**
	 * Find the range a number is in and return its description
	 *
	 * @param float $number
	 * @param array $ranges
	 * @return string|null
	 */
	protected function findRangeDescription($number, $ranges) {
		foreach($ranges as $range) {
			if($number >= $range['min'] && $number < $range['max']) {
				return $range['desc'];
			}
		}
		return null;
	}

	/**
	 * Hookable method for disallowing readability results for particular fields/pages
	 *
	 * @param Field $field
	 * @param Page $page
	 * @return bool
	 */
	public function ___allowReadabilityResults(Field $field, Page $page) {
		return true;
	}

	/**
	 * Attempt to get English language from $languages
	 *
	 * @return Language|null
	 * @throws WireException
	 */
	protected function getEnglishLanguage() {
		$languages = $this->wire()->languages;
		if(!$languages) return null;
		$english = $languages->getLanguage('english');
		if(!$english) $english = $languages->getDefault();
		return $english;
	}

	/**
	 * Config inputfields
	 *
	 * @param InputfieldWrapper $inputfields
	 */
	public function getModuleConfigInputfields($inputfields) {
		$modules = $this->wire()->modules;
		$languages = $this->wire()->languages;

		/** @var InputfieldCheckboxes $f */
		$f = $modules->get('InputfieldCheckboxes');
		$f_name = 'enabledTests';
		$f->name = $f_name;
		$f->label = $this->_('Enabled readability tests');
		$link_text = $this->_('about');
		foreach($this->info as $name => $info) {
			$f->addOption($name, "{$info['label']} – [$link_text]({$info['url']})");
		}
		$f->value = $this->$f_name;
		$inputfields->add($f);

		/** @var InputfieldRadios $f */
		$f = $modules->get('InputfieldRadios');
		$f_name = 'showResults';
		$f->name = $f_name;
		$f->label = $this->_('Show readability results');
		$f->addOption('click', $this->_('When the header action icon is clicked'));
		$f->addOption('always', $this->_('Always'));
		$f->value = $this->$f_name;
		$inputfields->add($f);

		if($languages && count($languages) > 1) {
			/** @var InputfieldRadios $f */
			$f = $modules->get('InputfieldRadios');
			$f_name = 'englishLanguage';
			$f->name = $f_name;
			$f->label = $this->_('English language');
			$f->description = $this->_('Select the English language in this website.');
			foreach($languages as $language) {
				$f->addOption($language->id, $language->name);
			}
			$f->value = $this->$f_name ?: $this->getEnglishLanguage();
			$inputfields->add($f);
		}

	}

}
