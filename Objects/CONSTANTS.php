<?php

/**
 * Constants used in the code
 */
class CONSTANTS
{
    /**
     * Should the company names be split on '-'
     */
    public const SHOULD_SPLIT = true;

    /**
     * The name of the view that contains the companies which aren't matched to zoho
     */
    public const NON_MATCHED_VIEW_NAME = "notlinked";

    /**
     * The number of matches to skip when searching for a company
     */
    public const NB_FIRST_MATCH_TO_SKIP = 0;

    /**
     * The number of matches to return when searching a company
     */
    public const NB_MATCH_TO_RETURN = 5;
    public const USE_SOLR = 1;
    public const UNASSIGNED_COMPANY_ID = 9998;
    public const UNEMPLOYED_COMPANY_ID = 9999;
    public const DEFAULT_LIMIT = 10;
    public const DEFAULT_OFFSET = 0;
    public const ZOHO_RESULTS_PER_SHEET = 100;

}

class SOLR
{
    public const FIELD_URL = "baseurl";
    public const FIELD_IS_NEW = "isnew";
    public const IP = "127.0.0.1";
    public const PORT = "8983";
    public const SEARCH_ENDPOINT = "select";
    public const CORE_NAME = "companies2";
    public const QUERY_FORMAT = "http://%s:%s/solr/%s/%s?q=";
    public const DEFAULT_MINIMUM_WORD_LENGTH = 3;
    public const FUZZY_SEARCH_MAX_DISTANCE = 7;
}

class AJAX_ARGS
{
    public const CONTACT_ID = "id";
    public const SKILLS = "skills";
    public const EXPERIENCES = "experiences";
    public const EXP_COMPANY = "company";
    public const EXP_REGION = "region";
    public const EXP_DESC = "desc";
    public const DESCRIPTION = "about";
    public const COMPANY_NAME = "name";
    public const COMPANY_URL = "url";
    public const EXP_TITLE = "title";
    public const EXP_TIME_FRAME = "timeFrame";
    public const TIME_START = "start";
    public const TIME_END = "end";
    public const TIME_LENGTH = "length";
    public const JSON = "json";
    public const BROKEN_ID = "brokenID";
    public const CORRECT_ID = "correctID";
    public const QUERY = "query";
    public const LIMIT = "limit";
    public const OFFSET = "offset";
    public const SHOULD_UNASSIGN = "unassign";
}
