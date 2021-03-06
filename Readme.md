![1](/images/1.png)
![2](/images/2.png)
![3](/images/3.png)
![4](/images/4.png)

# Pollitic

Pollitic is a web application that allows people to vote for their preferred candidates using a secure mobile authenticated system that doesn't store any of the user's raw data.

## GET: '/api/ongoing' and GET: '/api/closed'
These two routes behave in exactly the same way, the only difference being that one returns only ongoing polls and one returns only closed ones. |

Returns a JSON Object with 'data', which contains a 'polls' attribute which is an array of all public polls.

Required GET Variables

| Name | Required | Details |
|--|--|--|
| sort | no | A string that is either 'new' or 'hot'. new returns polls sorted from newest to oldest, 'hot' returns polls sorted by their votecount |
| number | no | An integer that specifys how many posts the requests want to retrieve
| page | no | An integer that specifys which page of the data to be retrieved. defaults to 1
| perPage | no | An integer that specifys how many polls should be shown per page for pagination. defaults to 10

Example response:

    {
        "status": "success",
        "data": {
            "polls": [
                {
                    "id": 1,
                    "name": "test",
                    "description": "test",
                    "imageLink": null,
                    "password": null,
                    "requirePhoneAuth": "True",
                    "isListed": "True",
                    "created_at": "2018-10-23 10:42:59",
                    "updated_at": "2018-10-23 10:42:59"
                    "isClosed": "True",
                    "closingDate": {
                        "date":"0256-10-25 15:40:45.000000",
                        "timezone_type":3,
                        "timezone":"UTC"
                    },
                    "totalVotes" : 0
                }
            ]
            "page" : 1,
            "totalPages": 1,
            "perPage": 10
        }
    }


## POST: '/api/poll/create'

Creates a poll based on the request.

**Variables you need to submit:**

| Name | Required | Details | Example |
|--|--|--|--|
| name | yes | name of the poll |  |
| description | yes | description of poll |  |
| password | no | if this value is provided, the poll will require all voters to enter this password |  |
| requirePhoneAuth | yes | string boolean value of if this poll requires phone authentication | 'True' |
| isListed | yes | string boolean value of if this poll is unlisted or not | 'True' |
| candidates | yes | an array of candidates | ['name1', 'name2'] |
| closingDate | yes | a unix timestamp of when the poll should be closed | '1540935877' |
| image | no | an image file that is 4MB max. |  |
| recaptcha | yes | a token aquired from the google recaptcha on the site. |  |

The response to this will __always__ have a 'status' property. The status will either be 'success' or 'error'

***error***

- If there is an error, there will always be an error attribute that contains the error message. These error messages can be displayed straight to the user. There will also __sometimes__ be a field attribute, which will give the field that the error occured because of. This will only happen when the error is related to a field, and not some other part of the app.

Example Error:

    {
        "status": "error",
        "error": "გთხოვთ შეიყვანოთ გამოკითხვის სათაური",
        "field": "name"
    }


***success***

 - If the status is success, then there will be a message attribute saying the poll is created, and a data attribute with a poll attribute containing the info for the poll that was created.

## GET: '/api/poll/{id}/view'
Returns a JSON Object with 'data' , which contains a 'poll' attribute with all the poll and candidate information. If the poll is not found, a 404 is returned.

Example response: 

     {
        "status":"success",
        "data":{
            "poll":{
                "id":1,
                "name":"test",
                "description":"test",
                "imageLink":null,
                "password":null,
                "requirePhoneAuth":"True",
                "isListed":"True",
                "created_at":"2018-10-23 10:42:59",
                "updated_at":"2018-10-23 10:42:59",
                "candidates":[
                    {
                        "id":1,
                        "name":"name2",
                        "number":null,
                        "websiteLink":null,
                        "socialMediaLink":null,
                        "imageLink":'',
                        "created_at":"2018-10-23 10:42:59",
                        "updated_at":"2018-10-23 10:42:59",
                        "poll_id":1,
                        "voteCount":0
                    }
                ]
            }
        }
    }




## POST: '/api/poll/{id}/vote/'

The {id} is the unique ID of the poll currently being voted on.

**Variables you need to submit:**

| Name | Required | Details |
|--|--|--|
| number | no | Phone number of voter. a georgian phone number(599123456) without any whitespace or dashes. |
| candidateId | yes| ID of the candidate the person voted for. The candidateId for each candidate is provided in the response of the '/api' route as the 'id' attribute. |
| gender | no| Any string, the backend just treats it as a string and puts it in the database. The backend chose to treat this as just a string instead of a binary value because it is the frontend's job to conform to traditional gender roles. |
| age | no| Any positive integer.  |
| recaptcha | yes | a token aquired from the google recaptcha on the site. |  |

The response to this will either return a 404 because the poll was not found, or it will __always__ have a 'status' property. The status will either be 'success' or 'error'

***error***

> If the status is 'error', then it will __always__ have an 'error'
> property that will be one of these messages:
> 
>  - 'გთხოვთ შეიყვანოთ სწორი 9 ნიშნა ნომერი!'
>  
>  - 'ეს ნომერი ერთხელ უკვე გამოყენებული იქნა!'
>  
>  - 'გთხოვთ აირჩიოთ კანდიდატი!'
>  
>  - 'გთხოვთ აირჩიოთ ამ გამოკითხვის შესაბამისი კანდიდატი!' **This should never occur, as both the vote ID and poll ID should be already
> existing values retrieved from the API**
>  
>  - 'მესიჯის გაგზავნისას დაფიქსირდა შეცდომა.'
>  - 'შეყვანილი პაროლი არასწორია!' **If and only if the poll has a password**
>
> There may also be a 'field' attribute that will specify exactly which field the error occured because of, if this is the case.



Error Example:

    {
        "status": "error",
        "error": "გთხოვთ აირჩიოთ კანდიდატი!",
        "field": "candidateId"
    }

***success***

> If the status is 'success', and the poll requires a phone number to vote, then there will be a data attribute with 2
> more attributes: 
> 
>  1. 'message' 		 The message will contain a string saying the SMS is sent.
>  2. 'link' 		 The link will be the route that should be used for verification of this specific voter, as the link contains an unique id
> to their vote.
>
> If the poll does not require a phone number to vote, the status will be success, and there will only be a 'message' attribute.


## POST: '/api/vote/{id}/verify/'


 This route is used to submit the pin the user got as an SMS message and verify the vote.
 You must post into this with one variable

| Name | Required | Details |
|--|--|--|
| pin| required | 5 digit pin received by the user via SMS |

This route may return 3 things:

 - A 404 because a vote with the given ID was not found. This should never happen as you should __always__ be acquiring this link from a post request to '/api/vote/'
 - A JSON with a 'status' property of 'success'. This means that the vote has been verified.
 - An error saying 'შეყვანილი ვერიფიკაციის კოდი არასწორია!'


## POST: '/api/poll/{id}/delete/'


 This route is used to remove a poll.

| Name | Required | Details |
|--|--|--|
| password | required | an administrator password |

This route may return 3 things:

 - A 404 because a poll with the given ID was not found.
 - A JSON with a 'status' property of 'success'. This means that the poll has been deleted.
 - An error saying 'პაროლი არასწორია!'

