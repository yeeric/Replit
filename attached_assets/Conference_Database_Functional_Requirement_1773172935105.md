Conference Database Functional Requirements

Please note:  Instruction on Application Development will be given in class.  Until we have covered this material, you probably do not have enough information to complete this assignment.

You will write a fully functioning web-based interface for the conference database  that we have been working on in class thus far.

Your application will be for the conference organizers only, therefore it does not have to be flashy.  Instead, functionality is the key!  Your application should use PHP and must be able to work with (almost) any DBMS (therefore you should be using PDOs, not mysqli functions).   Your application must support the following:

display all members of a particular organizing sub-committee  (allow the user to choose the sub-committee from a drop down menu).
for a particular hotel room, list all of the students housed in this room.
display the conference schedule for a particular day.
list the sponsors (company name) and their level of sponsorship
for a particular company, list the jobs that they have available.
list all jobs available.
show the list of conference attendees as 3 lists: students, professionals, sponsors.
add a new attendee.  If the attendee is a student, add them to a hotel room. 
show the total intake of the conference broken down by total registration amounts and total sponsorship amounts.
add a new sponsoring company
delete a sponsoring company and it's associated attendees
switch a session's day/time and/or location.
These requirements are a minimum. You may find that you need to add additional data and or functionality to make your application realistic, or to demonstrate that it works. You may assume that user input is correct so input syntax checking can be minimal.  You should gracefully handle the case(s) where your query does not return any results.

How you organize your application is up to you, but your application must have more than one web page -- not all functionality should be on the same page.  Your home page should be called conference. This can be an html or a php page.  To use your application, we should NOT have to directly access any other URL other than the main conference page.  (You may have links on the home page to other functionality which is acceptable). 

Information that would be well suited to a tabular display must be displayed as a table.  Proper html tags must be used for headings, paragraphs, lists etc.    Your application doesn't need to be flashy, but it needs to be visually appealing with at least one image.  Make it look as professional as you can.  I'm not asking for an expert web application, I'm just looking for some reasonable effort.

Your application must use PHP to generate the dynamic content (ie. accessing the back end database and displaying the results) and must be able to work with (almost) any DBMS (therefore you must be using PDOs, not the mysqli api). You may use only basic html, css and php to code your website, no fancy tools.