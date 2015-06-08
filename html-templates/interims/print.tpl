<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Interim Reports</title>
<style type="text/css">
body, dd, li, p {
    font-family: Palatino Linotype, Book Antiqua, Palatino, serif !important;
	font-size: 13pt !important;
}
body {
	margin: auto;
	orphans: 3;
	padding: .25in 0.5in;
	width: 8.5in;
	widows: 3;
}
header, h1, h2, h3, dt {
	font-family: Helvetica Neue, Helvetica, Arial, Verdana, sans-serif !important;
}
p {
	margin: 1em 0 0 !important;
}
a, a:link, a:visited {
	color: #666;
	font-family: Consolas, Menlo, Monaco, Courier, monospace;
	font-size: 12pt;
	text-decoration: none;
}
header {
	display: block;
	border-bottom: 1px solid #666;
	padding-bottom: 0.25em;
}
.student {
	page-break-before: always;
}
.student:first-of-type {
	page-break-before: auto;
}
header h1 {
	font-size: 24pt;
	margin: 0;
}
header h1 .pretitle {
	display: block;
	font-size: 12pt;
}
header h3 {
	font-size: 12pt;
	margin: 1em 0 0;
}
header .advisor {
	float: right;
	font-size: 12pt;
	font-weight: 900;
	line-height: 8pt;
	padding-top: 20pt;
	text-align: right;
}
header .advisor a {
	display: block;
	font-size: 10pt;
	font-weight: normal;
	line-height: 18pt;
	text-align: center;
}
dt {
	clear: both;
	color: #666;
	float: left;
	font-size: 9pt;
	line-height: 16pt;
	margin: 0;
	text-transform: uppercase;
}
dd {
	margin: 0 0 .5em 1.25in;
}
dd.grade {
	font-weight: 900;
	page-break-before: avoid;
	page-break-after: avoid;
}

dt.assessment,
dt.comments {
	display: inline-block;
	float: none;
	page-break-after: avoid;
}

dd.assessment,
dd.comments {
	clear: left;
	line-height: 1.4;
	margin: 0.5em .25in 1em;
}

.assessment p,
.comments p {
	line-height: 1.4 !important;
}

article, section {
	display: block;
	margin-bottom: 1em;
}
.report {
	border-bottom: 1px dotted #999;
}
.report h2 {
	page-break-after: avoid;
}
dl {
	page-break-before: avoid;
}

.empty-report {
    text-align: center;
    font-style: italic;
}
</style>
</head>
<body>
{$lastReport = false}
{if !is_array($data)}
	{$data = array($data)}
{/if}

<section class="student">
{foreach item=Report from=$data}
	{if $lastReport->StudentID != $Report->StudentID}
		{if $lastReport}
</section><section class="student">
		{/if}
		<header>
			{if $Report->Student->Advisor}
				<div class="advisor">
					Advisor: {$Report->Student->Advisor->FullName}
					<a href="mailto:{$Report->Student->Advisor->Email}">{$Report->Student->Advisor->Email}</a>
				</div>
			{/if}
			<h1>
				<span class="pretitle">Interim report for</span>
				{$Report->Student->FullName}
			</h1>
			<h3 class="term">{$Report->Term->Title|escape}</h3>
		</header>
	{/if}
	<article class="report">
	
		<h2>{$Report->Section->Title|escape}</h2>
		
		<dl>
		{if $Report->Section->Instructors[0]->FullName}
			<dt class="instructor">Instructor</dt>
			<dd class="instructor">
				{$Report->Section->Instructors[0]->FullName}
				{if $Report->Section->Instructors[0]->Email}
					<a href="mailto:{$Report->Section->Instructors[0]->Email}">&lt;{$Report->Section->Instructors[0]->Email}&gt;</a>
				{/if}
			</dd>
		{/if}
		{if $Report->Grade}
			<dt class="grade">Current Grade</dt>
			<dd class="grade">{$Report->Grade}</dd>
		{/if}
		{if $Report->Comments && $Report->Comments}
			<dt class="comments">Comments</dt>
			<dd class="comments">{$Report->Comments}</dd>
		{/if}
		</dl>
		
	</article>
	
	{$lastReport = $Report}
{foreachelse}
    <p class="empty-report">No reports matching your criteria are available</p>
{/foreach}
</section>

</body>
</html>