<?php namespace ProcessWire;
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
		$english = $this->getEnglishLanguage();
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

		// Get the (English) text
		if($inputfield->useLanguages) {
			$english = $this->englishLanguage ?: $this->getEnglishLanguage();
			$property = "value{$english}";
			$text = $inputfield->$property;
		} else {
			$text = $inputfield->value;
		}
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
			$append .= "<div><a href='{$this->info[$name]['url']}' target='_blank'>{$this->info[$name]['label']}:</a> <b>$result</b></div>";
		}
		$append .= '</div>';
		$inputfield->appendMarkup($append);
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
