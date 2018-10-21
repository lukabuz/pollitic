
# Pollitic

Pollitic is a web application that allows people to vote for their preferred candidates using a secure mobile authenticated system that doesn't store any of the user's raw data.


## GET: '/api/'


Returns a JSON Object with 'data' being an array of candidates

Example response: 

    {
        "status": "success",
        "data": [
            {
                "id": 1,
                "name": "",
                "number": "",
                "websiteLink": "",
                "socialMediaLink": "",
                "imageLink": "",
                "voteCount": 1
            }
        ]
    }



## POST: '/api/vote/'


**Variables you need to submit:**

| Name | Required | Details |
|--|--|--|
| number | yes| Phone number of voter. 9 digit number(599123456) without any whitespace or dashes. |
| candidateId | yes| ID of the candidate the person voted for. The candidateId for each candidate is provided in the response of the '/api' route as the 'id' attribute. |
| gender | no| Any string, the backend just treats it as a string and puts it in the database. The backend chose to treat this as just a string instead of a binary value because it is the frontend's job to conform to traditional gender roles. |
| age | no| Any positive integer.  |

The response to this will __always__ have a 'status' property.

If the status is 'error', then it will __always__ have an 'error' property that will be one of these messages:

 - 'გთხოვთ შეიყვანოთ სწორი 9 ნიშნა ნომერი!'
  
 - 'ეს ნომერი ერთხელ უკვე გამოყენებული იქნა!'

If the status is 'success', then there will be a data attribute with 2 more attributes: 

 1. 'message'
		 The message will contain a string saying the SMS is sent.
 2. 'link'
		 The link will be the route that should be used for verification of this specific voter, as the link contains an unique id to their vote.


## POST: '/api/vote/{id}/verify/'


 This route is used to submit the pin the user got as an SMS message and verify the vote.
 You must post into this with one variable

| Name | Required | Details |
|--|--|--|
| pin| required | 5 digit pin received by the user via SMS |

This route may return 3 things:

 - A 404 because a vote with the given ID was not found. This should never happen as you should __always__ be acquiring this link from a post request to '/api/vote/'
 - A JSON with a 'status' property of 'success'
 - An error saying 'შეყვანილი ვერიფიკაციის კოდი არასწორია!'

