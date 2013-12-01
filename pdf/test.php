<?php

include_once("pdf/lib/pdfBase.php");

class test extends pdfBase {
	public function __construct(libPDFInterface $pdf) {
		parent::__construct($pdf);

		$this->SetAutoPageBreak(true, $this->bMargin + $this->FontSizePt);
		$this->SetTopMargin($this->tMargin + $this->FontSizePt);
	}

	public function onHeader() {
		$this->SetDefaultFont();
		$this->SetY($this->tMargin - $this->FontSizePt);
		$this->Cell(0, 0, "This text is within the header!", 0, 1, "C");
	}

	public function onFooter() {
		$this->SetDefaultFont();
		$this->SetY($this->h - $this->bMargin + $this->FontSizePt / 2);
		$this->Cell(0, 0, "This text is within the footer!", 0, 1, "C");
	}

	public function pageRect() {
		$this->SetDrawColor("#0000FF");
		$this->Rect($this->lMargin, $this->tMargin, $this->w - $this->lMargin - $this->rMargin, $this->h - $this->tMargin - $this->bMargin);
		$this->SetDrawColor("#FF0000");
		$this->Line($this->lMargin, $this->PageBreakTrigger, $this->w - $this->rMargin, $this->PageBreakTrigger);
		$this->SetDrawColor("#000000");
	}

	public function display($args) {
		$this->setName("Page");

		$this->AddPage();

		$this->FlowText("As you read this text, you will notice that we seamlessly flow from ");
		$this->FlowText("sections of bold text, to", "B");
		$this->FlowText(" sections in a completely different font ", array("name" => "times"));
		$this->FlowText("or a completely different size.", array("size" => $this->FontSizePt * 2 / 3));
		$this->FlowText(" This text will even flow over lines, and even if we use really long words like ");
		$this->FlowText("antidisestablishmentarianism", "U");
		$this->FlowText(" or ");
		$this->FlowText("supercalifragilisticexpialidocious.", "I");
		$this->FlowText(" Using the FlowText() function is fun!");
		$this->Ln();
		$this->Ln();

		$this->FlowText("We can embed line feeds into flow text lines like this:\n\n\nThey are handled just like you'd expect.");
		$this->FlowText("\nThey're ignored at the start and end of the line. - There are no gaps before or after this line.\n");
		$this->Ln();
		$this->FlowText("This is the next line.");
		$this->Ln();
		$this->Ln();

		$this->FlowText("We're testing alignment now: Left", null, "L");
		$this->Ln();
		$this->FlowText("We're testing alignment now: Centered", null, "C");
		$this->Ln();
		$this->FlowText("We're testing alignment now: Right", null, "R");
		$this->Ln();

		$this->FlowText("We're testing alignment with line feeds now:\nLeft", null, "L");
		$this->Ln();
		$this->FlowText("We're testing alignment with line feeds now:\nCentered", null, "C");
		$this->Ln();
		$this->FlowText("We're testing alignment with line feeds now:\nRight", null, "R");
		$this->Ln();

		$this->FlowText("The following text is Left aligned:");
		$this->Ln();
		$this->FlowText("Left, ", null, "L");
		$this->FlowText("Centered, ", null, "C");
		$this->FlowText("Right.", null, "R");
		$this->Ln();
		$this->FlowText("Centered, ", null, "C");
		$this->FlowText("Left, ", null, "L");
		$this->FlowText("Right.", null, "R");
		$this->Ln();
		$this->FlowText("Centered, ", null, "C");
		$this->FlowText("Right, ", null, "R");
		$this->FlowText("Left.", null, "L");
		$this->Ln();
		$this->FlowText("Left alignment is infectious.", null, "R");
		$this->Ln();
		$this->Ln();

		$this->FlowText("The following text is Centered:");
		$this->Ln();
		$this->FlowText("Right, ", null, "R");
		$this->FlowText("Centered.", null, "C");
		$this->Ln();
		$this->Ln();

		$this->FlowText("The following text is Right aligned:");
		$this->Ln();
		$this->FlowText("Centered, ", null, "C");
		$this->FlowText("Right.", null, "R");
		$this->Ln();
		$this->Ln();

		$this->FlowText("While we don't recommend you combine crazy alignment tricks with line feeds, it works as if there was a Ln() call and new FlowText() call at each line feed. See:");
		$this->Ln();
		$this->FlowText("Le\nft, ", null, "L");
		$this->FlowText("Cent\nered, ", null, "C");
		$this->FlowText("Rig\nht.", null, "R");
		$this->Ln();
		$this->FlowText("Cent\nered, ", null, "C");
		$this->FlowText("Le\nft, ", null, "L");
		$this->FlowText("Rig\nht.", null, "R");
		$this->Ln();
		$this->FlowText("Cent\nered, ", null, "C");
		$this->FlowText("Rig\nht, ", null, "R");
		$this->FlowText("Le\nft.", null, "L");
		$this->Ln();
		$this->FlowText("Rig\nht, ", null, "R");
		$this->FlowText("Cent\nered.", null, "C");
		$this->Ln();
		$this->FlowText("Cent\nered, ", null, "C");
		$this->FlowText("Rig\nht.", null, "R");
		$this->Ln();
		$this->Ln();

		$this->FlowText("Alignment flows along as we go, even when we flow from ", null, "C");
		$this->FlowText("sections of bold text, to", "B", "C");
		$this->FlowText(" sections in a completely different font ", array("name" => "times"), "C");
		$this->FlowText("or a completely different size.", array("size" => $this->FontSizePt * 2 / 3), "C");
		$this->FlowText(" This text will even flow over lines, and even if we use really long words like ", null, "C");
		$this->FlowText("antidisestablishmentarianism", "U", "C");
		$this->FlowText(" or ", null, "C");
		$this->FlowText("supercalifragilisticexpialidocious.", "I", "C");
		$this->FlowText(" Using alignment with the FlowText() function is fun!", null, "C");
		$this->Ln();
		$this->Ln();

		$this->HTMLText("<p>As you read this text, you will notice that we seamlessly flow from <b>sections of bold text</b>, to <i>sections in italics</i> or <u>underlined sections</u> This text will even flow over lines, and even if we use really long words like <u>antidisestablishmentarianism</u> or <i>supercalifragilisticexpialidocious.</i></p><p>This is in a separate paragraph.<br/>And this is on the next line</p>This text is outside the paragraph but gets it's <b>own</b> anyway.<br/>And another new line.<p>Using the HTMLText() function is fun!</p>");
		$this->Ln();
		$this->HTMLText("This is another paragraph, with some default styling. We're not using a 'p' tag so as to test that <b>bold</b> and <b><u>other formatting</u></b> works properly.<br/>And new lines too.", array(
			"size" => $this->FontSizePt * 2 / 3,
			"name" => "times"
		));
		$this->Ln();
		$this->HTMLText("This is another paragraph, with some default styling and crazy alignment. We're not using a 'p' tag so as to test that <b>bold</b> and <b><u>other formatting</u></b> works properly.<br/>And new lines too.", array(
			"size" => $this->FontSizePt * 2 / 3,
			"name" => "times"
		), "C");
		$this->Ln();

		$this->pageRect();

		foreach(array("L", "C", "R") as $a)
			for($i = 0; $i < 10; $i++) {
				$this->SetY($this->PageBreakTrigger - $this->FontSizePt * $i / 5);
				$this->SetDrawColor("#00FF00");
				$this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
				$this->SetDrawColor("#000000");

				$this->FlowText("Let's write a lot of text to ensure that it flows over page boundaries properly. We're really pushing the boat out here, gotta make sure it's all correct. Hmm .... Lots of text, LOATS OOOOVVV TEEEAAACCKKSST. Ahem. So lots of text, lots and lots of text. Long words too, antidisestablishmentarianism, supercalifragilisticexpialidocious, .... refrigerator?... ok, that's enough.", null, $a);
				$this->Ln();
				$this->Ln();
				$this->pageRect();
			}

		$this->FlowText("Let's test all the fancy functions of tables!");
		$this->Ln();
		$this->Ln();

		$this->TableCell("We're testing vertical alignment:\n\nIt's awesome!", 50, null, "C", 1);
		$this->TableCell("This is middle aligned.", 50, array(
			"background" => "#FFFF00"
		), "L", 1, null, "M");
		$this->TableCell("This is bottom aligned.", null, array(
			"background" => "#00FFFF"
		), "R", 1, null, "B");
		$this->Ln();

		$this->TableCell("This is a test.\nThis is a really long line containing lots and lots of text and stuff.\nThis is a shorter line.\n\nThat was a blank line, just for kicks.\nThis line contains a really long word. It is supercalifragilisticexpialidocious.\nWe also know the word Antidisestablishmentarianism.", 100, array(
			"background" => "#FFFF00",
			"style" => "BU"
		), "L", 1);
		$this->TableCell("This is a second cell, it should be aligned properly, especially once we're off the first line.\n\nThis is another line to check the alignment.", null, array(
			"background" => "#00FFFF"
		), "R", 1);
		$this->Ln();

		$this->TableCell("This is a second row, it should only appear after the first one has been completed.\n\nAnother blank line.\nAnd some more text.", 70, array(
			"background" => "#FF00FF",
			"style" => "U"
		), "C", 1);
		$this->TableCell("This is the second column of the second row. We're going to repeat the first row again after this one, just to make sure that everything is set properly.", null, array(
			"background" => "#C0C0C0",
			"color" => "#FF0000",
			"size" => 12,
			"style" => "B"
		), "C", 1);
		$this->Ln();

		$this->TableCell("This is a test.\nThis is a really long line containing lots and lots of text and stuff.\nThis is a shorter line.\n\nThat was a blank line, just for kicks.\nThis line contains a really long word. It is supercalifragilisticexpialidocious.\nWe also know the word Antidisestablishmentarianism.", 100, array("size" => 12, "style" => "B"), "R", 1);
		$this->TableCell("This is a second cell, it should be aligned properly, especially once we're off the first line.\n\nThis is another line to check the alignment.", null, "U", "L", 1);
		$this->Ln();

		$this->TableCell("This is here to ensure that the offsetting is working properly\n\nIt's also middle aligned.", 70, array(
			"background" => "#FFFF00",
			"style" => "BU"
		), "R", 1, null, "M");
		$this->TableCell("This is a really long line. We're testing how text in cells is transferred to the next page, and so on and so forth. As such, this is going to contain a massive amount of text, which we will use to test this, then we'll repeat the first line again, just to make sure that nothing's broken. If it has, we'll have to fix the bug, which may or may not be fun. So yeah, that's what's happening. Like it or not.\n\nAnnoyingly, I didn't make this line long enough the first time, so I'm resorting to tricks like this:\n\nAnd this:\n\nTo just make it fricking long enough. What do I have to do?\n\nWhat about now, is this long enough, dammit?!?!?!?!\n\nAnd now it looks like I need it to be even longer!\n\nSigh\n\nI'm going to cheat and just duplicate what's above:\n\nDuplication START:\n\nThis is a really long line. We're testing how text in cells is transferred to the next page, and so on and so forth. As such, this is going to contain a massive amount of text, which we will use to test this, then we'll repeat the first line again, just to make sure that nothing's broken. If it has, we'll have to fix the bug, which may or may not be fun. So yeah, that's what's happening. Like it or not.\n\nAnnoyingly, I didn't make this line long enough the first time, so I'm resorting to tricks like this:\n\nAnd this:\n\nTo just make it fricking long enough. What do I have to do?\n\nWhat about now, is this long enough, dammit?!?!?!?!\n\nDuplication END!\n\nGlad that's over, now on with the program.", 40, array(
			"background" => "#FF00FF",
			"style" => "U"
		), "C", 1);
		$this->TableCell("And this is here just to finish off the line.\n\nIt's bottom aligned.", null, array(
			"background" => "#00FFFF",
			"size" => 12,
			"style" => "B"
		), "L", 1, null, "B");
		$this->Ln();

		$this->TableCell("This is a test.\nThis is a really long line containing lots and lots of text and stuff.\nThis is a shorter line.\n\nThat was a blank line, just for kicks.\nThis line contains a really long word. It is supercalifragilisticexpialidocious.\nWe also know the word Antidisestablishmentarianism.", 100, array("size" => 12, "style" => "B"), "R", 1);
		$this->TableCell("This is a second cell, it should be aligned properly, especially once we're off the first line.\n\nThis is another line to check the alignment.", null, "U", "L", 1);
		$this->Ln();

		return true;
	}
}
