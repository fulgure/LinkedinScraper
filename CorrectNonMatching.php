<?php

/**
 * @TODO Implement lazy-loading for companies. As of right now, the page will timeout if too many companies are loaded at once
 */
require_once "includeAll.php";
$companies = GetAllNonMatchedCompanies();
$filteredComps = [];
foreach ($companies as $comp) {
	array_push($filteredComps, new EPotentialURL($comp["id"], $comp["url"], CONSTANTS::SHOULD_SPLIT));
}
$allNames = GetAllNamesFromCorrectCompanies(CONSTANTS::SHOULD_SPLIT);
$matchingCompanies = [];
foreach ($filteredComps as $comp) {
	array_push($matchingCompanies, GetClosestMatchingCompaniesFromList($comp, $allNames));
}

?>

<head>
	<style>
		#search * {
			width: 10%;
		}

		.enabled {
			color: Teal !important;
			font-weight: bold;
		}
	</style>
</head>

<body>
	<?php
	$cnt = 0;
	foreach ($matchingCompanies as $match) {
		$title = urldecode($match[0]["badName"][1]);
		$id = urldecode($match[0]["badName"][2]);
	?>
		<div id="<?= $cnt ?>" class="comp" style="display:none;text-align:center;">
			<h3 class="<?= $cnt ?> name" id="<?= $id ?>" style="color:red;display:hidden">
				<span id="num" class="<?= $cnt ?>"><?= $cnt + 1 ?>.</span>
				<span id="title" class="<?= $cnt ?>"><?= urldecode($match[0]["badName"][1]) ?></span>
				<span id="id" class="<?=$cnt?>"> (<?=$id?>)</span>
			</h3>
			<?php
			if ($match !== null) {
				foreach ($match as $possibility) { ?>
					<button class="<?= $cnt ?> possibility" style="color:green;font-size:12pt;" id="<?= $possibility["fixedName"][2] ?>" value="<?= $possibility["fixedName"][1] ?>"><?= $possibility["fixedName"][1] ?> (<?= intval($possibility["perc"]) ?>%)</button>
			<?php }
			} ?>

		</div>
	<?php
		$cnt++;
	} ?>
	<br>
	<div style="text-align:center;">
		<button class="unassign" style="color:red;font-size:16pt;margin-right:3%" onclick="SetUnassigned()">UNASSIGN</button>
		<button class="unemploy" style="color:red;font-size:16pt;" onclick="SetUnemployed()">UNEMPLOYED</button>
		<br>
		<button class="manual" style="color:red;font-size:16pt;margin-top:2%" onclick="SetIdManually()">ENTER ID</button>
		<br>
		<br>
	</div>
	<hr>
	<div style="text-align:center;">
		<h3 style="color:blue">Recherche</h3>
		<input type="text" id="searchBar" name="search" style="font-size:16pt" />
		<div id="searchTerms" style="display:block;margin-top:1%;margin-bottom:1%;">
		</div>
		<div id="searchResults">
			<h3 class="notFound" style="color:red;display:none;">NO RESULTS</h3>
		</div>
	</div>

	<br>
	<hr>

	<div style="text-align:center" id="search">
		<button class="previous" size="10" style="color:blue;font-size:16pt;margin-top:3%; margin-right:3%" onclick="ChangeCompanyToMatch(-1)">PREVIOUS</button>
		<input type="text" id="currPage" style="font-size:16pt;font-weight:700;margin:3% 3% 0% 0%;text-align:center" maxlength="6" size="6">
		<button class="skip" size="10" style="color:blue;font-size:16pt;margin-top:3%" onclick="ChangeCompanyToMatch(1)">NEXT</button>
		<br>
		<button class="changeIndex" style="color:blue;font-size:16pt;margin-top:1%;" onclick="JumpToCompany()">JUMP TO</button>
	</div>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script>

		const AUTO_SEARCH = true;
		const NB_COMPANIES = <?= count($matchingCompanies) ?>;
		const DEFAULT_MINIMUM_WORD_LENGTH = 3;
		const TITLE_SELECTOR = "%s";
		const FIX_LINKEDIN = "ajax/FixLinkedin.php";
		const DB_SEARCH = "ajax/SearchCompanyByName.php";

		const KEYS = {
			LEFT: 37,
			UP: 38,
			RIGHT: 39,
			DOWN: 40,
		};

		const FIXES = {
			CHANGE_ID: 0,
			UNEMPLOY: 1,
			UNASSIGN: 2
		};

		var currentNumber;
		var searchBar;
		var searchResults;
		var currPage;
		var searchTerms;

		var isTyping = false;
		var currTerms = '';
		var eventCalledByUser = false;

		$(document).ready(function() {

			currentNumber = 0;
			searchBar = $("#searchBar");
			searchResults = $('#searchResults');
			currPage = $('#currPage');
			searchTerms = $('#searchTerms');

			AddClickHandlerToButtons();
			searchBar.off('keyup').on("keyup", function() {
				eventCalledByUser = true;
				DBSearch($(this).val(), ParseDBSearch)
				SolrSearch($(this).val(), ParseSOLRSearch);
			})
			searchBar.on('focus', () => isTyping = true).on('blur', () => isTyping = false);
			currTerms = SplitCompanyName(GetCurrentTitle());
			AddSearchTerms(currTerms);
			if (AUTO_SEARCH){
				SolrSearchCurrentOption();

			}
			BindKeysToUI();
			RefreshUI();
		});

		/**
		 * Binds the UI to keyboard keys
		 *
		 * @return void
		 */
		function BindKeysToUI(){
			$(document).on("keyup", HandleKeyPress);
		}

		/**
		 * Handles key presses on the document
		 */
		function HandleKeyPress(event){
			if(IsKeyBound(event.keyCode)){
				switch(event.keyCode){
					case KEYS.LEFT:
						ChangeCompanyToMatch(-1);
						break;
					case KEYS.RIGHT:
						ChangeCompanyToMatch(1);
						break;
				}
			}else if(!isTyping && IsNumber(event.key)){
				//If the user presses 1, we want to simulate a click on the button that's at position 0 (I.E the first button)
				let val = Number(event.key) - 1;
				/* Disabled because it's easy to misclick */
				//$('.current .possibility').eq(val).click();
			}
		}

		/**
		 * Returns true if the character is a number
		 * 
		 * @return bool
		 */
		function IsNumber(char){
			let key = Number(char);
			
			if(key === 0 || isNaN(key) || char === null ||char === ' ')
				return false;

			return true;
		}

		/**
		 * Returns true if the selected key is bound to the UI
		 */
		function IsKeyBound(keyCode){
			for(let key in KEYS){
				if(KEYS[key] === keyCode)
					return true;
			}
			return false;
		}

		/**
		 * Returns the current company's title
		 *
		 * @return string
		 */
		function GetCurrentTitle() {
			return $('span#title.' + currentNumber).text();
		}

		function SolrSearch(text, callback) {
			if (Array.isArray(text))
				text = text.join(' ');

			/*if (text.length >= 3) {
				$.get("ajax/SolrWrapper.php", {
					query: text
				}, callback);
			}*/
		}

		/**
		 * Splits a company name
		 */
		function SplitCompanyName(name, minWordLength = DEFAULT_MINIMUM_WORD_LENGTH) {
			name = name.replace(/-/g, ' ');
			nameParts = name.split(' ');
			result = [];
			nameParts.forEach(function(el) {
				if (el.length >= DEFAULT_MINIMUM_WORD_LENGTH)
					result.push(el);
			});

			return result;
		}

		/**
		 * Adds a click handler to the possibility buttons
		 *
		 * @return void
		 */
		function AddClickHandlerToButtons() {
			$(".possibility").off('click').on("click", function() {
				var goodID = $(this).attr("id");
				SubmitChange(GetCurrentBadID(), goodID);
			});
		}

		/**
		 * Parses the search results, and updates the UI
		 */
		function ParseSOLRSearch(data) {
			if (data.code > 0) {
				$('.searchResult').remove();
				var results = JSON.parse(data.data);
				if (currTerms.length == 0)
					currTerms = data.terms.reduce((result, val) => result += ' ' + val);

				if (results.response.docs.length > 0) {
					$('.notFound').hide();
					results.response.docs.forEach(function(val, i) {
						AddPossibilityButton(val.id, val.baseurl, 'solr');
					})
					AddClickHandlerToButtons();
				} else {
					$('.notFound').show();
				}
			}
			newTerms = SplitCompanyName(searchBar.val());
			isEqual = true;
			if(currTerms.length !== newTerms.length)
				isEqual = false;
			else{
				currTerms.forEach((val, i) => isEqual &= (val == newTerms[i]))
			}
			if(!isEqual){
				currTerms = newTerms;
				RemoveAllSearchTerms();
				AddSearchTerms(currTerms);
			}
			RefreshUI();
			eventCalledByUser = false;
		}

		function ParseDBSearch(data) {
			if (data.code > 0) {
				$('.searchResult').remove();
				if(data.data.length > 0){

					$('.notFound').hide();
					data.data.forEach(function(val){
						AddPossibilityButton(val[0], val[1], 'db');
					});
					AddClickHandlerToButtons();
				} else {
					$('.notFound').show();
				}
			}
			newTerms = SplitCompanyName(searchBar.val());
			isEqual = true;
			if(currTerms.length !== newTerms.length)
				isEqual = false;
			else{
				currTerms.forEach((val, i) => isEqual &= (val == newTerms[i]))
			}
			if(!isEqual){
				currTerms = newTerms;
				RemoveAllSearchTerms();
				AddSearchTerms(currTerms);
			}
			RefreshUI();
			eventCalledByUser = false;
		}

		/**
		 * Removes all search terms
		 *
		 * @return void
		 */
		function RemoveAllSearchTerms() {
			$('.searchTerm').remove();
		}

		/**
		 * Toggles the enabled class on a button
		 */
		function ToggleButtonAndSearch(btn) {
			$(btn).toggleClass('enabled');
			SolrSearch(GetSearchTerms(), ParseSOLRSearch);
		}
		
		/**
		 * Adds buttons for the search terms
		 */
		function AddSearchTerms(terms) {
			terms.forEach(el => AddSearchTermButton(el));
		}

		/**
		 * Returns all the search terms
		 *
		 * @return void
		 */
		function GetSearchTerms() {
			result = '';
			$('.searchTerm.enabled').each(function(i) {
				result += $(this).text() + ' ';
			});
			return result;
		}
		/**
		 * Adds a search term button with text that's equal to the val parameter
		 */
		function AddSearchTermButton(val) {
			searchTerms.append(`
			<button class="searchTerm enabled" style="color:red;font-size:16pt;" onclick="ToggleButtonAndSearch(this)">${val}</button>
			`);
		}

		function DBSearch(text, callback = ParseSOLRSearch){
			$.get({
				url: DB_SEARCH,
				data: {<?=AJAX_ARGS::COMPANY_NAME?>:text},
				success: callback
			});
		}

		/**
		 * Adds a possibility button with a set id and url
		 */
		function AddPossibilityButton(id, url, classes = '') {
			searchResults.append(`
			<button style="color:green;font-size:12pt;margin:0.3% 0% 0.3% 0%" id="${id}" class="searchResult possibility ${classes}" value="${url}">${url} (${id})</button>
			`);
		}

		/**
		 * Submits changes
		 */
		/*
		function SubmitChange(badID, goodID, shouldUnassign = false) {
			data = {
				"brokenID": badID,
				"correctID": goodID
			};
			if (shouldUnassign)
				data.unassign = true;

			$.get("ajax/FixLinkedin.php", data, function(data) {
				if (data.Code > 0) {
					ChangeCompanyToMatch(1, false);
				} else {
					alert("Error making the request");
				}
			});
		}*/

		function HandleFixLinkedinSuccess(data){
			if (data.Code > 0) {
					ChangeCompanyToMatch(1, false);
				} else {
					alert("Error making the request");
				}
		}

		/**
		 * Jumps to the company at the index indicated by the UI
		 *
		 * @return void
		 */
		function JumpToCompany() {
			newVal = parseInt(currPage.val()) - 1;
			if (newVal >= 0) {
				relativePos = newVal - currentNumber;
				ChangeCompanyToMatch(relativePos, false);
			}
		}

		const FORWARD = 0;
		const BACKWARD = 1;
		/**
		Change l'entreprise montrée à l'utilisateur à partir d'une position relative
		RelativePosition = 1 : Montre la prochaine entreprise dans la liste
		RelativePosition = -1 : Montre l'entreprise précédente dans la liste
		**/
		function ChangeCompanyToMatch(newRelativePosition, checkForDuplicates = false) {
			if (newRelativePosition == 0)
				return;
			else if (newRelativePosition > 0) {
				direction = FORWARD;
				incrementVal = 1;
			} else {
				direction = BACKWARD;
				incrementVal = -1;
			}
			oldComp = GetCurrentTitle();
			/* currentNumber commence à 0, alors que NB_COMPANIES commence à 1 */
			if (direction = FORWARD && currentNumber + newRelativePosition >= NB_COMPANIES)
				currentNumber = NB_COMPANIES - 1;
			/* Relative position est une position relative, donc possiblement négatif */
			/* Si relativePosition = -1, on montre l'entreprise précédente */
			else if (direction == BACKWARD && currentNumber - abs(newRelativePosition) < 0)
				currentNumber = 0;
			else
				currentNumber += newRelativePosition;

			if (checkForDuplicates) {
				shouldSkip = true;
				firstLoop = true;
				while (shouldSkip) {
					currentNumber += incrementVal;
					newComp = GetCurrentTitle();
					if (newComp !== oldComp || currentNumber == 0 || currentNumber >= NB_COMPANIES) {
						shouldSkip = false;
						if (firstLoop)
							currentNumber -= incrementVal;
					}
					oldComp = newComp;
					firstLoop = false;
				}
			}

			compName = GetCurrentTitle();
			currTerms = SplitCompanyName(compName)
			RemoveAllSearchTerms();
			SolrSearchCurrentOption();
			AddSearchTerms(currTerms)
			RefreshUI();
		}

		/**
		 * Refreshes the UI
		 *
		 * @return void
		 */
		function RefreshUI() {
			currPage.prop('value', currentNumber + 1);
			if (currTerms.length > 0 && !eventCalledByUser)
				searchBar.prop('value', currTerms.join(' '));
			$(".current").hide().removeClass("current");
			$("#" + currentNumber + ".comp").show().addClass("current");
			console.log(currentNumber);
		}

		/**
		 * Searchs the current option on Solr
		 *
		 * @return void
		 */
		function SolrSearchCurrentOption() {
			SolrSearch(currTerms, ParseSOLRSearch);
		}

		const UNEMPLOYED_ID = <?= CONSTANTS::UNEMPLOYED_COMPANY_ID ?>;
		const UNASSIGNED_ID = <?= CONSTANTS::UNASSIGNED_COMPANY_ID ?>;

		/**
		 * Changes the company id of all experiences that have badID to goodID
		 */
		function SubmitChange(badID, goodID) {
			data = {
				brokenID: badID,
				correctID: goodID
			};
			CallFixLinkedin(data);
		}

		/**
		 * Calls the page to match a company with Zoho
		 */
		function CallFixLinkedin(data, callback = HandleFixLinkedinSuccess){
			$.get(FIX_LINKEDIN, data, HandleFixLinkedinSuccess);
		}

		/**
		 * Sets the current company to unemployed
		 *
		 * @return void
		 */
		function SetUnemployed() {
			CallFixLinkedin({brokenID: GetCurrentBadID(), correctID:UNEMPLOYED_ID});
		}

		function GetCurrentBadID(){
			return badID = $("h3." + currentNumber).attr("id");
		}

		/**
		 * Sets the current company to unassigned
		 *
		 * @return void
		 */
		function SetUnassigned() {
			CallFixLinkedin({brokenID: GetCurrentBadID(), <?=AJAX_ARGS::SHOULD_UNASSIGN?>: true});
		}

		function SetIdManually(){
			var goodID = prompt("Enter the correct ID");
			
			if(goodID.length > 0){
				CallFixLinkedin({brokenID: GetCurrentBadID(), correctID: goodID});
			}
			else{
				alert("Incorrect ID");
			}
		}

	</script>
</body>