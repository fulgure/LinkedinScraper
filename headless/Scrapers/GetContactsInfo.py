from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.common.keys import Keys
from time import sleep
from math import floor
from datetime import datetime
import json
import random
import jsonpickle
import re
import requests
from urllib.parse import quote
import sys
import ctypes
from consts import consts
from consts import selectors


class ECompany:
    """Represents a company
    """

    def __init__(self, name: str, url: str):
        self.name = name
        self.url = url


class ETimeFrame:
    """Represents a Time Frame
    """

    def __init__(self, start: str, end: str, length: str):
        self.start = start.strip(' \t\n\r').replace("\r\n", "")
        self.end = end.strip(' \t\n\r').replace("\r\n", "")
        self.length = length.strip(' \t\n\r').replace("\r\n", "")


class EExperience:
    """Represents a professionnal experience (A.K.A a job)
    """
    company: ECompany = None
    title: str = None
    timeFrame: ETimeFrame = None
    region: str = None
    desc: str = None

    def __init__(self, company: ECompany, title: str):
        self.company = company
        self.title = title


class EContact:
    """Represents a contact (A.K.A someone)
    """
    id: str
    firstName: str
    lastName: str
    about: str
    currTitle: str
    employerLinkedin: str
    experiences: list
    skills: list

    def __init__(self, firstName: str, lastName: str):
        self.firstName = firstName
        self.lastName = lastName


def SetupWebDriver() -> webdriver:
    """Sets the web driver up
    """
    chrome_options: Options = Options()
    chrome_options.add_argument(consts.USER_DATA_DIR)
    return webdriver.Chrome(chrome_options=chrome_options)


def GetEContactFromURL(driver: webdriver, url: str) -> EContact:
    """Returns a [EContact] from an url

    Args:
        driver (webdriver): The current page's driver
        url (str): The contact's linkedin URL

    Returns:
        EContact: An EContact filled with the info found on the linkedin
    """
    if(LoadProfile(driver, url)):
        # Unpacks the tuple returned by GetContactName to create contact
        result: EContact = EContact(*GetContactName(driver))
        result.about = GetContactDescription(driver)
        result.experiences = GetAllExperiences(driver)
        # @TODO Handle unemployed people
        if(len(result.experiences) > 0):
            result.currTitle = result.experiences[0].title
            result.employerLinkedin = result.experiences[0].company.url
        result.skills = GetSkills(driver)
        return result
    else:
        return False


def GetSkills(driver: webdriver) -> list:
    """Returns all the skills

    Args:
        driver (webdriver): driver

    Returns:
        list<string>: all the skills
    """
    result: list = list()
    for skill in driver.find_elements(By.CSS_SELECTOR, selectors.SKILLS):
        result.append(skill.text.strip(' \t\n\r'))
    return result


def stripNewlines(string: str) -> str:
    """Removes all newlines from a string

    Args:
        string (str): The string to strip

    Returns:
        str: The strings, with '\r' and '\n' removed
    """
    return string.replace("\r", "").replace("\n", "")


def GetContactDescription(driver: webdriver) -> str or None:
    """Returns the text from a linkedin profile's about section

    Args:
        driver (webdriver): The driver

    Returns:
        str: The text in the about section
    """
    try:
        return stripNewlines(driver.find_element(By.CSS_SELECTOR, selectors.ABOUT).text.strip(' \t\n\r'))
    except:
        return None


def LoadProfile(driver: webdriver, url: str) -> bool:
    """ Opens the specified LinkedIn profile, then moves around the page in order to load all the lazy-loaded content
    Args:
        driver (webdriver): The page's driver
        url (str): A linkedin profile
    """
    driver.get(url)
    try:
        WebDriverWait(driver, consts.PAGE_LOAD_TIMEOUT).until(
            EC.presence_of_element_located(
                (By.CLASS_NAME, selectors.PAGE_LOADED))
        )
    except:
        if(driver.find_element(By.ID, "captcha-internal") != None):
            ctypes.windll.user32.MessageBoxW(
                0, "Captcha détécté.\n L'éxécution de du script reprendra lorsque vous appuierez sur le bouton \"OK\".", "HELP", (0x30 | 0x40000))
            return False

    LoadLazyLoadedBlocks(driver)
    ExpandAllText(driver)
    return True


def LoadLazyLoadedBlocks(driver: webdriver.Chrome) -> None:
    """Move the viewport around to load all the lazy loaded sections

    Args:
        driver (WebDriver): The driver
    """
    # Scroll to the bottom to start the lazy loading
    driver.execute_script("scroll(0,document.body.scrollHeight);")
    shouldLoop: bool = True
    # Loop until all the loading icons have disappeared
    while shouldLoop:
        try:
            el: WebElement = driver.find_element(
                By.CLASS_NAME, selectors.LAZY_LOADING_IMG)
            # We need to see the icon for the content to actually load
            ScrollToElem(driver, el)
        except:
            # If we're here, it means selenium couldn't find a lazy loading icon anymore
            shouldLoop = False


def ScrollToElem(driver: webdriver, el: WebElement) -> None:
    """Scrolls the page to put the specified element in view

    Args:
        driver (webdriver): The page's webdriver
        el (WebElement): The element to put into view
    """
    driver.execute_script('arguments[0].scrollIntoView({block:"center"});', el)


def GetContactName(driver: webdriver) -> tuple:
    """Returns a contact's first and last name from a linkedin profile, as a tuple(firstname,lastname)

    Args:
        driver (webdriver): The page's driver

    Returns:
        tuple: (firstName, lastname)
    """
    fullName: list = driver.find_element(
        By.CSS_SELECTOR, selectors.CONTACT_NAME).text.strip().split(' ')
    return (fullName[0], fullName[1])


def GetAllExperiences(driver: webdriver) -> list:
    """Returns a list of EExperience, containing all the experiences in a linkedin profile

    Args:
        driver (webdriver): The page's driver

    Returns:
        list[EExperience]: A list of EExperience
    """
    experiences: list = list()
    try:
        blocks: WebElement = driver.find_elements(
            By.CLASS_NAME, selectors.EXP_BLOCK)
    except:
        pass

    for b in blocks:
        experiences.append(GetEExperienceFromBlock(b))
    return experiences


def GetExpDescFromBlock(expBlock: WebElement) -> str or None:
    """Returns the description of an experience

    Args:
        expBlock (WebElement): The experience's block

    Returns:
        str or None: The experience's description
    """
    try:
        return stripNewlines(expBlock.find_element(By.CLASS_NAME, selectors.EXP_DESC).text.replace('<br>', ''))
    except:
        return None


def ExpandAllText(driver: webdriver) -> None:
    """Clicks all the "See more" links on the page to load all the info

    Args:
        driver (webdriver): The page's driver
    """
    # The js to execute in order to remove an element from the DOM
    removeArgScript: str = 'document.getElementsBy{0}("{1}")[0].remove();'
    # The selectors for the different show more blocks
    # Format is such :
    # [
    #   Show more -> (selector type, selector),
    #   Show less -> (selector type, selector)
    # ]
    elems: list = [
        # "Show X more experience" button
        [(By.CLASS_NAME, selectors.SHOW_MORE_EXP),
         (consts.BY_CLASS, selectors.HIDE_EXP)],
        # "...show more" link in an experience's description
        [(By.CLASS_NAME, selectors.SHOW_MORE_EXP_DESC),
         (consts.BY_CLASS, selectors.HIDE_EXP_DESC)],
        # "...show more" link in the about section, no "show less" link so nothing to remove
        [(By.CSS_SELECTOR, selectors.SHOW_MORE_ABOUT), (None)],
        # "Show more" link in the skills section
        [(By.CLASS_NAME, selectors.SHOW_MORE_SKILLS),
         (consts.BY_CLASS, selectors.SHOW_MORE_SKILLS)]
    ]

    for elem in elems:
        try:
            el = driver.find_element(
                elem[0][0], elem[0][1])
            ScrollToElem(driver, el)
            el.click()

            if(elem[1] != None):
                driver.execute_script(removeArgScript.format(
                    elem[1][0], elem[1][1]))
        except:
            continue


def GetEExperienceFromBlock(expBlock: WebElement) -> EExperience:
    """Returns an [EExperience] from an experience block

    Args:
        expBlock (WebElement): The experience block

    Returns:
        EExperience: An EExperience representing the experience
    """
    comp: ECompany = GetECompanyFromExperience(expBlock)
    title: str = GetTitleFromBlock(expBlock)
    result: EExperience = EExperience(comp, title)

    result.timeFrame = GetETimeFrameFromBlock(expBlock)
    result.region: str = GetRegionFromBlock(expBlock)
    result.desc = GetExpDescFromBlock(expBlock)

    return result


# @TODO Check for multiple jobs
# EG https://www.linkedin.com/in/laurent-carminati-469669120/
def GetTitleFromBlock(expBlock: WebElement) -> str:
    """Returns the job's title from an experience block

    Args:
        expBlock (WebElement): The experience block

    Returns:
        str: The job's title
    """
    # https://www.linkedin.com/in/christelle-andersen-3a09b921/ -> Format différent des autres
    # C'est pour ce format que l'on se sert d'un if
    try:
        # Si plusieurs postes dans une entreprise (comme christelle ci-dessus) le sélécteur est "a > div > div > h3"
        titlePossibleLocation: WebElement = expBlock.find_element(
            By.CSS_SELECTOR, selectors.SINGLE_EXP)
        elemExists = True
    except:
        elemExists = False

    if(elemExists):
        return titlePossibleLocation.text.strip(' \t\n\r')
    else:
        # @TODO Fix for multiple jobs in the same company (right now only the first one will be returned)
        return expBlock.find_element(By.CSS_SELECTOR, selectors.MULTIPLE_EXP).text.strip(' \t\n\r')


# @TODO Fix for multiple jobs in the same company (right now only the first one will be returned)
def GetRegionFromBlock(expBlock: WebElement) -> str or None:
    """Returns a job's region from an experience block

    Args:
        expBlock (WebElement): The experience block

    Returns:
        str or None: The job's region
    """
    try:
        return expBlock.find_element(By.CSS_SELECTOR, selectors.REGION).text.strip(' \t\n\r')
    except:
        return None


# @TODO Fix for multiple jobs in the same company (right now only the first one will be returned)
def GetETimeFrameFromBlock(expBlock: WebElement) -> ETimeFrame or None:
    """Returns an [ETimeFrame] from an experience block

    Args:
        expBlock (WebElement): The experience block

    Returns:
        ETimeFrame or None: The timeframe
    """
    try:
        dateRange: list = expBlock.find_element(
            By.CSS_SELECTOR, selectors.DATE_RANGE).text.split('–')
        start: str = dateRange[0]
        end: str = dateRange[1]
        length: str = expBlock.find_element(
            By.CSS_SELECTOR, selectors.EXP_LENGTH).text
        return ETimeFrame(start, end, length)
    except:
        return None


def GetECompanyFromExperience(expBlock: WebElement) -> ECompany:
    """Returns an [ECompany] from an experience

    Args:
        expBlock (WebElement): The experience block

    Returns:
        ECompany: The company as ECompany
    """
    try:
        compName: str = expBlock.find_element(
            By.CLASS_NAME, selectors.COMPANY_NAME).text.strip(' \t\n\r')
    except:
        compName: str = expBlock.find_element(
            By.CSS_SELECTOR, selectors.ALTERNATE_COMPANY_NAME).text.strip(' \t\n\r')
    # HREF = "/company/companyName/"
    #                 ^^^^^^^^^^^^
    compURL: str = expBlock.find_element(
        By.TAG_NAME, 'a').get_attribute('href')
    return ECompany(compName, compURL)


def GetContactsToSearch(driver: webdriver, profilesToSkip: int = 0) -> list:
    """Get the first [NB_CONTACTS_TO_GET] contacts to research

    Args:
        driver (webdriver): The driver

    Returns:
        list: The URLs of the contacts to search
    """
    request: requests.Response = requests.get(
        f"{consts.URL_GET_CONTACTS}?{consts.ARG_LIMIT_GET_CONTACTS}={consts.NB_CONTACTS_TO_GET}&{consts.ARG_OFFSET_GET_CONTACTS}={profilesToSkip}")
    employees = json.loads(request.text)
    return employees


def SaveEmployeesData(employees: EContact) -> None:
    """Saves a list of EContacts in Database

    Args:
        employees (EContact): A list of EContacts to be saved
    """
    global profilesToSkip
    data: str = jsonpickle.encode(employees)
    requests.post(
        consts.URL_SAVE_CONTACTS, {consts.ARGS_JSON: data})
    print("Total contacts saved since script started running : " +
          str(totalUpdatedContacts))
    print("Total skipped contacts : " + str(profilesToSkip))


def lerp(valAsPercentage: float, min: int or float, max: int or float) -> float:
    """ Linearly interpolates a percentage, and returns the number as a value
        between [min] and [max]
        Exemple : lerp(0.33, 0, 10) = 3.33

    Args:
        valAsPercentage (float): The position of the value to interpolate in the current scale
        min (int,float): The minimal value in the desired scale
        max (int,float): The maximal value in the desired scale

    Returns:
        float: A number whose distance to [min] and [max] is [valAsPercentage]
    """
    if(min > max):
        return min
    scale:float = (max - min)
    return valAsPercentage * scale + min


def JustifyNumber(number: int or float, requiredLength: int, decimalPlaces: int = 2) -> str:
    """Pads a number with 0 until it's the required length.

    Args:
        number (float, integer): The number to justify
        requiredLength (int): The desired length
        decimalPlaces (int, optional): The desired number of decimal places. Defaults to 2.

    Returns:
        str: The number, as a string, padded with leading zeroes
    """
    numberAsStr:str = ("{:." + str(decimalPlaces) + "f}").format(number)
    if(decimalPlaces > requiredLength):
        print("JustifyNumber() : WARNING: decimalPlaces > requiredLength")
        requiredLength = requiredLength + decimalPlaces

    if(len(numberAsStr) > requiredLength):
        print("JustifyNumber() : WARNING: Number length exceeds required length, returning number")
        return numberAsStr

    return numberAsStr.rjust(requiredLength, "0")


def SleepForRandomAmountOfTime(minSleep=consts.MIN_SLEEP_TIME, maxSleep=consts.MAX_SLEEP_TIME, log: bool=True) -> None:
    """Sleeps for a random amount of time

    Args:
        minSleep (float, optional): Minimum amount of time to sleep. Defaults to MIN_SLEEP_TIME.
        maxSleep (float, optional): Maximum amount of time to sleep. Defaults to MAX_SLEEP_TIME.
        log (bool, optional): Should the function print to the console. Defaults to True.
    """
    timeToSleep: float = lerp(random.random(), minSleep, maxSleep)
    # "{.2f}".format(float) returns the float as a string, with only 2 decimal points
    print(f"Sleeping for {timeToSleep:.2f} seconds")
    ttsLength: int = len(f"{timeToSleep:.2f}")

    for i in range(floor(timeToSleep)):
        sys.stdout.write(" " + str(JustifyNumber(timeToSleep, ttsLength)))
        sleep(1)
        timeToSleep -= 1
    sys.stdout.write(" " + str(JustifyNumber(timeToSleep, ttsLength)))
    print("\nDone sleeping")
    sleep(timeToSleep)


def ShowRecommendedPauseAlert() -> None:
    """Shows a message box recommending a pause to the user
    """
    time:datetime = datetime.now()
    ctypes.windll.user32.MessageBoxW(0,
        f"Le script a récupéré {consts.NB_CONTACTS_BEFORE_RECOMMENDED_PAUSE} contacts.\n"
        + "Il est recommendé de faire une pause d'au moins 10 minutes afin d'éviter que le compte utilisé soit restreint\n"
        + "L'éxécution du script reprendra dès que cette boîte de dialogue sera fermée.\n"
        + f"Message affiché à {str(time.hour).rjust(2)}:{str(time.minute).rjust(2)}.{str(time.second).rjust(2)}", "PAUSE RECOMMENDÉE",
        (0x30 | 0x40000))

driver: webdriver = SetupWebDriver()
# Open linkedin's homepage in order to look less suspicious
driver.get("https://www.linkedin.com")
profilesToSkip: int = 0
nbLoops: int = 0
nbPauses: int = 0
totalUpdatedContacts: int = 0
contactsSinceLastRecommendedPause: int = 0
shouldLoop: bool = True
while shouldLoop:
    nbPauses += 1

    while nbLoops < (consts.MAX_LOOPS_PER_RUN * nbPauses):
        nbLoops += 1

        contacts: list = GetContactsToSearch(driver, profilesToSkip)
        if(len(contacts) == 0):
            shouldLoop = False
            break

        result: list = list()
        for c in contacts:
            try:
                contact = GetEContactFromURL(
                    driver, c["linkedin"])
                SleepForRandomAmountOfTime()
                if(contact == False):
                    continue

                contact.id = c['id']
                result.append(contact)
            except:
                print("Error getting contact info : " + c['linkedin'])
                profilesToSkip += 1
                continue

        if(contactsSinceLastRecommendedPause > consts.NB_CONTACTS_BEFORE_RECOMMENDED_PAUSE):
            ShowRecommendedPauseAlert()
            contactsSinceLastRecommendedPause = 0

        totalUpdatedContacts += len(result)
        contactsSinceLastRecommendedPause += len(result)
        SaveEmployeesData(result)

    print("Sleeping for " + str(consts.SLEEP_TIME_BETWEEN_RUNS) + " seconds\n")
    for i in reversed(range(consts.SLEEP_TIME_BETWEEN_RUNS)):
        sys.stdout.write(str(i).rjust(
            len(str(consts.SLEEP_TIME_BETWEEN_RUNS)), "0") + " ")

        if(i % 10 == 0):
            sys.stdout.write("\n")

        sleep(1)
    nbLoops = 0


print("DONE\n" * 3)
