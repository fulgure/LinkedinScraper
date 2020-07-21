from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
import json
import pyperclip
import time
import math
import sys

#0 -> Fill company info
#1 -> Fill company name
FILL_NAMES = 1

#Only relevant if FILL_NAMES = 1
#How many cells around the current one should the script check for the value it's looking for
#The script will look above and below the current cell
#So if NB_FIELDS_TO_LOOKUP = 10, the script will check the 5 cells above and the 5 cells below the current one
NB_FIELDS_TO_LOOKUP = 10
#How many cells the script will look through in each direction
#The 
NB_LOOKUP_EACH_DIRECTION = math.floor(NB_FIELDS_TO_LOOKUP / 2)

#Value indicating that the seeked field wasn't found
FIELD_NOT_FOUND = -1

#Shorthands for arrow keys
LEFT = Keys.ARROW_LEFT
UP = Keys.ARROW_UP
DOWN = Keys.ARROW_DOWN
RIGHT = Keys.ARROW_RIGHT

#The url to the Zoho accounts list
ZOHO_ACCOUNTS_LIST = "https://crm.zoho.eu/crm/org20062984123/tab/Accounts/custom-view/56223000004156120/list?page=6&per_page=100"
#The JS to execute to open the sheet view from the accounts list
ZOHO_OPEN_SHEET = "crmListView.showSheet()"
#The URL where the script should get all the companies in JSON
INFO_URL = "http://127.0.0.1/LinkedInScraper/?page=5"

#Field names returned from the INFO_URL
COMPANY_LINKEDIN = "linkedIn"
COMPANY_NAME = "name"
COMPANY_SITE = 'website'
COMPANY_PHONE = 'phone'
COMPANY_EMPLOYEES = 'nbEmployees'
COMPANY_INDUSTRY = 'industry'
COMPANY_DESC = 'desc'

#The class of the active cell in Zoho Sheet View
ACTIVE_CELL_CLASS = 'fBEdit'


# Sets the web driver up
def SetupWebDriver():
    chrome_options = Options()
    chrome_options.add_argument("user-data-dir=selenium")
    return webdriver.Chrome(chrome_options=chrome_options)

# Get the data from all the companies
def GetCompaniesData(driver):
    driver.get(INFO_URL)
    text = driver.find_element(By.TAG_NAME, "pre").text
    return json.loads(text)

# Open zoho sheet from a zoho listing
def OpenZohoSheet(driver):
    driver.get(ZOHO_ACCOUNTS_LIST)
    WebDriverWait(driver, 100).until(
        EC.presence_of_element_located((By.ID, "moreTools"))
    )
    driver.execute_script(ZOHO_OPEN_SHEET)
    driver.switch_to.window(driver.window_handles[1])

# Moves the active cell in the specified directions
# If keys is an array, the function will send each key sequentially
#I.E keys = [DOWN,LEFT,DOWN] will move the active cell down, then left, then down
def MoveActiveCell(body, keys):
    for key in keys:
        body.send_keys(key)
        time.sleep(0.02)

# Moves to the field to the right of the active cell
def NextField(body):
    MoveActiveCell(body, RIGHT)

# Goes down a row and brings the active cell back to it's default position (the linkedin field)
def ResetPosNextRow(body):
    time.sleep(0.15)
    MoveActiveCell(body, Keys.HOME)
    time.sleep(0.15)
    MoveActiveCell(body, DOWN)
    time.sleep(0.1)
    MoveActiveCell(body, [RIGHT, RIGHT])
    time.sleep(0.1)

# Fill the website, phone, employees, industry, description fields
# Only fills them if the field is empty
def FillRow(body, data):
    values = [data[COMPANY_SITE], data[COMPANY_PHONE],
              data[COMPANY_EMPLOYEES], data[COMPANY_INDUSTRY]]
    global ActiveCell
    for field in values:
        NextField(body)
        if(len(field) > 0 and len(ActiveCell.text) <= 0):
            body.send_keys(field)
            body.send_keys(Keys.ENTER)
            body.send_keys(UP)
    NextField(body)
    if(len(ActiveCell.text) <= 0):
        pyperclip.copy(data[COMPANY_DESC])
        body.send_keys(Keys.CONTROL + "v")
        time.sleep(0.15)


# Returns a company's unique ID from a linkedin url
# https://www.linkedin.com/company/it-advanced-consulting/ -> it-advanced-consulting
def GetCompanyNameFromURL(url):
    # We split the string on slashes '/'
    #   0      /1/        2      /   3    /    4    /   5
    # {Protocol}://www.linkedin.com/company/{compName}/about/
    #                              What we want ^
    if(url.count('/') >= 4):
        return url.split('/')[4]
    else:
        return url

#Gets the companies info from INFO_URL, and fills the zoho sheet accordingly
def FillCompanyInfo():
    global ActiveCell
    driver = SetupWebDriver()
    data = GetCompaniesData(driver)
    for d in data:
        d[COMPANY_LINKEDIN] = GetCompanyNameFromURL(d[COMPANY_LINKEDIN])
        d[COMPANY_INDUSTRY] = d[COMPANY_INDUSTRY].strip(' ')
    OpenZohoSheet(driver)

    docBody = driver.switch_to.active_element
    ActiveCell = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, ACTIVE_CELL_CLASS))
    )
    # LAYOUT : ID Name LinkedIn Website Phone Employees Industry Description
    # DOWN : first row is header
    # RIGHT, RIGHT : ID -> Name -> LinkedIn
    MoveActiveCell(docBody, [RIGHT, RIGHT, DOWN])
    for currData in data:
        currLinkedin = GetCompanyNameFromURL(
            ActiveCell.text).replace('&', '%26')
        if(currData[COMPANY_LINKEDIN] != currLinkedin):
            print("couldn't match linkedin")
            print(currLinkedin)
            print((currData[COMPANY_LINKEDIN]))
            while currData[COMPANY_LINKEDIN] != currLinkedin:
                MoveActiveCell(docBody, DOWN)
                currLinkedin = GetCompanyNameFromURL(ActiveCell.text)

        FillRow(docBody, currData)
        ResetPosNextRow(docBody)

#Escapes special characters for HTML
#I.E & -> %26
def EscapeSpecialChars(txt):
    return txt.replace('&', '%26').replace('é', '%C3%A9').replace('ô', '%C3%B4').replace('–', '%E2%80%93').replace('â', '%C3%A2').replace('è', '%C3%A8')

#Checks whether it can find {txt} in the {distanceToCheck} fields in {direction} (UP, DOWN, LEFT, RIGHT)
#If it finds a match, it will return the number of cells it had to travel to find the matching cell
#If it doesn't find a match, it returns a value equal to the constant FIELD_NOT_FOUND
def CheckFieldsForString(body, direction, distanceToCheck, txt):
    global ActiveCell
    for i in range(1, distanceToCheck):
        MoveActiveCell(body, direction)
        if(FormatLinkedinString(ActiveCell.text) == txt):
            return i
    return FIELD_NOT_FOUND

#Formats a linkedinString to it's shorthand variant and escapes special characters
#https://www.linkedin.com/company/it-advanced-consulting/ -> it-advanced-consulting
def FormatLinkedinString(txt):
    return EscapeSpecialChars(
            GetCompanyNameFromURL(txt))

#Writes {name} in the corresponding sheet view field
#This function should only be called from the LinkedIn field, because the movements are hardcoded
def WriteNameFromLinkedInField(body, name):
    MoveActiveCell(body, LEFT)
    body.send_keys(name)
    MoveActiveCell(body, RIGHT)

#Gets the companies Name from INFO_URL, and fills the zoho sheet accordingly
def FillNames():
    global ActiveCell
    driver=SetupWebDriver()
    data=GetCompaniesData(driver)
    OpenZohoSheet(driver)

    docBody=driver.switch_to.active_element
    ActiveCell = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, ACTIVE_CELL_CLASS))
    )
    MoveActiveCell(docBody, [RIGHT, RIGHT, DOWN])
    for d in data:
        escapedName=FormatLinkedinString(ActiveCell.text)
        escapedNameInDB=FormatLinkedinString(d[COMPANY_LINKEDIN])
        #Field check order :
        #1. activeCell
        #2. One cell below it
        #3. NB_LOOKUP_EACH_DIRECTION cells above it
        #4. NB_LOOKUP_EACH_DIRECTION cells below it
        if(escapedNameInDB != escapedName):
            print(f'Could not find {escapedNameInDB}, found {escapedName}\r\n')
            if(CheckFieldsForString(docBody, DOWN, 1, escapedNameInDB) == FIELD_NOT_FOUND):
                MoveActiveCell(docBody, UP)
                distance=CheckFieldsForString(
                    docBody, UP, NB_LOOKUP_EACH_DIRECTION, escapedNameInDB)
                if(distance == FIELD_NOT_FOUND):
                    MoveActiveCell(docBody, [DOWN] * NB_LOOKUP_EACH_DIRECTION)
                    distance = CheckFieldsForString(docBody, DOWN, NB_LOOKUP_EACH_DIRECTION, escapedNameInDB)
                    if(distance == FIELD_NOT_FOUND):
                        MoveActiveCell(docBody, [UP] * (NB_LOOKUP_EACH_DIRECTION - 1))
                        continue

        WriteNameFromLinkedInField(docBody, d[COMPANY_NAME])
        MoveActiveCell(docBody, DOWN)
        if(len(ActiveCell.text) == 0):
            return

ActiveCell = None
if(FILL_NAMES == 1):
    FillNames()
else:
    FillCompanyInfo()
print('done')
sys.stdin.read(1)
