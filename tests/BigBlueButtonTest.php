<?php
/**
 * BigBlueButton open source conferencing system - http://www.bigbluebutton.org/.
 *
 * Copyright (c) 2016 BigBlueButton Inc. and by respective authors (see below).
 *
 * This program is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Lesser General Public License as published by the Free Software
 * Foundation; either version 3.0 of the License, or (at your option) any later
 * version.
 *
 * BigBlueButton is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along
 * with BigBlueButton; if not, see <http://www.gnu.org/licenses/>.
 */
namespace BigBlueButton;

use BigBlueButton\Core\ApiMethod;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use BigBlueButton\Parameters\EndMeetingParameters;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use BigBlueButton\Parameters\IsMeetingRunningParameters;
use BigBlueButton\Parameters\PublishRecordingsParameters;

/**
 * Class BigBlueButtonTest
 * @package BigBlueButton
 */
class BigBlueButtonTest extends TestCase
{
    /**
     * @var BigBlueButton
     */
    private $bbb;

    /**
     * Setup test class
     */
    public function setUp()
    {
        parent::setUp();

        foreach (['BBB_SECURITY_SALT', 'BBB_SERVER_BASE_URL'] as $k) {
            if (!getenv($k)) {
                $this->fail('$_SERVER[\'' . $k . '\'] not set in '
                    . 'phpunit.xml');
            }
        }

        $this->bbb = new BigBlueButton();
    }

    /* API Version */

    /**
     * Test API version call
     */
    public function testApiVersion()
    {
        $apiVersion = $this->bbb->getApiVersion();
        $this->assertEquals('SUCCESS', $apiVersion->getReturnCode());
        $this->assertEquals('1.0', $apiVersion->getVersion());
    }

    /* Create Meeting */

    /**
     * Test create meeting URL
     */
    public function testCreateMeetingUrl()
    {
        $params = $this->generateCreateParams();
        $url    = $this->bbb->getCreateMeetingUrl($this->getCreateParamsMock($params));
        foreach ($params as $key => $value) {
            $value = !is_bool($value) ? $value : ($value ? 'true' : 'false');
            $this->assertContains('=' . urlencode($value), $url);
        }
    }

    /**
     * Test create meeting
     */
    public function testCreateMeeting()
    {
        $params = $this->generateCreateParams();
        $result = $this->bbb->createMeeting($this->getCreateParamsMock($params));
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    /**
     * Test create meeting with a document URL
     */
    public function testCreateMeetingWithDocumentUrl()
    {
        $params = $this->getCreateParamsMock($this->generateCreateParams());
        $params->addPresentation('https://placeholdit.imgix.net/~text?txtsize=96&bg=30406B&txtclr=ffffff&txt=BigBlueButton&w=800&h=600');

        $result = $this->bbb->createMeeting($params);

        $this->assertCount(1, $params->getPresentations());
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    /**
     * Test create meeting with a document URL
     */
    public function testCreateMeetingWithDocumentEmbdded()
    {
        $params = $this->getCreateParamsMock($this->generateCreateParams());
        $params->addPresentation('bbb_logo.png', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'bbb_logo.png'));

        $result = $this->bbb->createMeeting($params);

        $this->assertCount(1, $params->getPresentations());
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    /* Join Meeting */

    /**
     * Test create join meeting URL
     */
    public function testCreateJoinMeetingUrl()
    {
        $joinMeetingParams = $this->generateJoinMeetingParams();
        $joinMeetingMock   = $this->getJoinMeetingMock($joinMeetingParams);

        $url = $this->bbb->getJoinMeetingURL($joinMeetingMock);

        foreach ($joinMeetingParams as $key => $value) {
            $value = !is_bool($value) ? $value : ($value ? 'true' : 'false');
            $this->assertContains('=' . urlencode($value), $url);
        }
    }

    public function testJoinMeeting()
    {
        $joinMeetingParams = $this->generateJoinMeetingParams();
        $joinMeetingMock   = $this->getJoinMeetingMock($joinMeetingParams);
        $joinMeetingMock->setRedirect(false);

        $response = $this->bbb->joinMeeting($joinMeetingMock);

        $this->assertEquals('FAILED', $response->getReturnCode());
    }

    /* Default Config XML */

    public function testGetDefaultConfigXMLUrl()
    {
        $url = $this->bbb->getDefaultConfigXMLUrl();
        $this->assertContains(ApiMethod::GET_DEFAULT_CONFIG_XML, $url);
    }

    public function testGetDefaultConfigXML()
    {
        $result = $this->bbb->getDefaultConfigXML();
        $this->assertNotNull($result->getRawXml());
    }

    /* End Meeting */

    /**
     * Test generate end meeting URL
     */
    public function testCreateEndMeetingUrl()
    {
        $params = $this->generateEndMeetingParams();
        $url    = $this->bbb->getEndMeetingURL($this->getEndMeetingMock($params));
        foreach ($params as $key => $value) {
            $this->assertContains('=' . urlencode($value), $url);
        }
    }

    public function testEndMeeting()
    {
        $meeting = $this->createRealMeeting($this->bbb);

        $endMeeting = new EndMeetingParameters($meeting->getMeetingId(), $meeting->getModeratorPassword());
        $result     = $this->bbb->endMeeting($endMeeting);
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    public function testEndNonExistingMeeting()
    {
        $params = $this->generateEndMeetingParams();
        $result = $this->bbb->endMeeting($this->getEndMeetingMock($params));
        $this->assertEquals('FAILED', $result->getReturnCode());
    }

    /* Is Meeting Running */

    public function testIsMeetingRunning()
    {
        $result = $this->bbb->isMeetingRunning(new IsMeetingRunningParameters($this->faker->uuid));
        $this->assertEquals('SUCCESS', $result->getReturnCode());
        $this->assertEquals(false, $result->isRunning());
    }

    /* Get Meetings */

    public function testGetMeetingsUrl()
    {
        $url = $this->bbb->getMeetingsUrl();
        $this->assertContains(ApiMethod::GET_MEETINGS, $url);
    }

    public function testGetMeetings()
    {
        $result = $this->bbb->getMeetings();
        $this->assertNotNull($result->getMeetings());
    }

    /* Get meeting info */

    public function testGetMeetingInfoUrl()
    {
        $meeting = $this->createRealMeeting($this->bbb);

        $url = $this->bbb->getMeetingInfoUrl(new GetMeetingInfoParameters($meeting->getMeetingId(), $meeting->getModeratorPassword()));
        $this->assertContains('=' . urlencode($meeting->getMeetingId()), $url);
        $this->assertContains('=' . urlencode($meeting->getModeratorPassword()), $url);
    }

    public function testGetMeetingInfo()
    {
        $meeting = $this->createRealMeeting($this->bbb);

        $result = $this->bbb->getMeetingInfo(new GetMeetingInfoParameters($meeting->getMeetingId(), $meeting->getModeratorPassword()));
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    public function testGetRecordingsUrl()
    {
        $url = $this->bbb->getRecordingsUrl(new GetRecordingsParameters());
        $this->assertContains(ApiMethod::GET_RECORDINGS, $url);
    }

    public function testGetRecordings()
    {
        $result = $this->bbb->getRecordings(new GetRecordingsParameters($this->faker->sha1));
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }

    public function testPublishRecordingsUrl()
    {
        $url = $this->bbb->getPublishRecordingsUrl(new PublishRecordingsParameters($this->faker->sha1, true));
        $this->assertContains(ApiMethod::PUBLISH_RECORDINGS, $url);
    }

    public function testPublishRecordings()
    {
        $result = $this->bbb->publishRecordings(new PublishRecordingsParameters('non-existing-id-' . $this->faker->sha1, true));
        $this->assertEquals('FAILED', $result->getReturnCode());
    }

    public function testDeleteRecordingsUrl()
    {
        $url = $this->bbb->deleteRecordingsUrl(new DeleteRecordingsParameters($this->faker->sha1));
        $this->assertContains(ApiMethod::DELETE_RECORDINGS, $url);
    }

    public function testDeleteRecordings()
    {
        $result = $this->bbb->deleteRecordings(new DeleteRecordingsParameters('non-existing-id-' . $this->faker->sha1));
        $this->assertEquals('SUCCESS', $result->getReturnCode());
    }
}
