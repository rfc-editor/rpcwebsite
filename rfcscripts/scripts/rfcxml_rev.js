/* Copyright The IETF Trust 2020 All Rights Reserved */
/* $Id: rfcxml_rev.js,v 1.1.1.1 2016/02/08 21:03:51 priyanka Exp $ */
/*
 * Functions to use in the XSL Transformation of rfc-index.xml into
 * rfc-index.html. These functions take the doc-id values and generate
 * appropriate URLs to retrieve the documents.
 *
 * Install this script in the scripts directory of the root server directory:
 *    $SERVER_ROOT/scripts/rfcxml.js
 */

/*
 * Write the link to the HTML document.
 * type  : The type of document being linked to
 * num   : The number part of the document id
 * anchor: The visible text for the link.
 */
function writeDocUrl(type, numStr, anchor) {
   var num = Number(numStr);
   switch (type) {
   case 'rfc':
   case 'bcp':
   case 'std':
   case 'fyi':
      // These are the most common cases.
      document.write('<a href="http://www.rfc-editor.org/info/' + type + num + '"');
      document.write(' target="_blank">' + anchor + '</a>');
      break;
   case 'ien':
      // For unknown (or forgotten) reasons, the IEN documents are stored
      // differently from the other types.
      document.write('<a href="http://www.rfc-editor.org/' + type + '/' +
                     type + '/' + type +  num + '.txt"');
      document.write(' target="_blank">' + anchor + '</a>');
      break;
   default:
      // For odd cases, just write the anchor text.
      document.write(anchor);
   }
}

/*
 * Break the document id into the parts for the link to the document whose
 * record is being processed.
 */
function doMainDocLink(docid) {
   var type   = docid.substr(0,3);
   var num    = docid.substr(3);
   var anchor = docid.substr(3);
   writeDocUrl(type.toLowerCase(), num, anchor);
}

/*
 * Break the document id into parts for the subsidiary links in a document
 * record.
 */
function doStandardDocLink(docid) {
   var type   = docid.substr(0,3);
   var num    = docid.substr(3);
   writeDocUrl(type.toLowerCase(), num, docid);
}

var months = new Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');


function showGeneratedDate()
{
	if (Date.parse(document.lastModified) != 0) {
		var timeStamp = new Date(document.lastModified);
		document.write ('<p style="text-decoration:underline;">CREATED ON: ' 
				+ months[timeStamp.getMonth()]
				+ ' - ' + timeStamp.getDate() + ' - ' + yearToString(timeStamp)
				+ '</p>');
	}
}

function yearToString(aTime) {
   // JavaScript 1.2+ have method for this.
   if (aTime.getFullYear) {
      return aTime.getFullYear();
   }
   // simulate for JavaScript 1.1
   var y = aTime.getYear()
   if (y < 1000) y += 1900;
   return y;
}

function showRows1000(numRows) {
    var i;
    for (i=1; i <=1000; i++){
    document.getElementById("row" + i).style.display = "";
    }

    var j;
    for (j=1001; j<= numRows; j++){
     document.getElementById("row"+ j).style.display = "none";
    }
}

function showRowsAll(numRows) {
    var i;
    for (i=1; i <=numRows; i++){
    document.getElementById("row" + i).style.display = "";
    }
 }
