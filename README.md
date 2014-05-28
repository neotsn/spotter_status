#SKYWARN Storm Spotter Status

<img src="http://i1.wp.com/thepizzy.net/blog/wp-content/uploads/2014/05/skywarn_650.png?resize=300%2C300" alt="SKYWARN Logo by thepizzy.net" title="SKYWARN" align="right" />
 
SKYWARN Storm Spotter Status provides updates with the latest "Spotter Activation Statement" from the National Weather Service's (NWS) Hazardous Weather Outlook (HWO) bulletins.

The process is simple:

1. Authenticate with your Twitter Account
2. Follow @NOAAalerts on Twitter (user clicks button to act)
3. Select NWS Office Locations to monitor for new HWOs
4. Receive Spotter Activation Statements as Direct Messages on Twitter

**Full documentation and a user guide is provided on at [thepizzy.net] [user_doco]**

##System Workflow
###Scheduled Outlook updates
Once the user has authenticated and chosen their NWS Office locations, a CRON job runs every 15 minutes on the server (a constraint imposed by my webhost's TOS).

Every 15 minutes, the CRON checks for NWS Office ids that were checked more than 30 minutes ago, and that are NWS Office ids currently assigned to a user.
* New NWS Office locations will fetch the latest HWO within 15 minutes
* Existing NWS Office locations will check for an update to their HWO within 30 minutes

###Outlook processing
If new/updated HWOs are found:

1. They are first processed to remove excess whitespace triggering false updates
2. A MD5 hash is generated from the cleansed outlook body text
3. A DB check is made for the hash and if none exists, the process continues
4. The outlook is parsed for its printed timestamp, affected counties list, spotter activation statement
5. All the pieces are then saved to the database.
6. For every spotter activation statement, a Direct Message is issued to the users requesting HWOs for that NWS Office location

##Contributing, Questions, Help
This is my first publicly open-source project, and as such, I'm open to contributions. I am still fleshing out things like roadmap and all the various features of GitHub. If you have ideas, feature requests, or code enhancements, get in touch with me on twitter [@neotsn] [neotsn_twitter], or here, and we can discuss.

##Future Plans
I know the NWS offers several other products beside HWO, and in the future I plan to investigate their other offerings to determine which, if any, would benefit from the same kind of service. I am also entertaining the idea of expanding this service offering to only alerts if a selected county is affected.

##License
This SKYWARN Spotter Status service is copyright [thepizzy.net] [website].
It is unaffiliated with the National Weather Service, National Oceanic and Atmospheric Administration, and SKYWARN.
SKYWARN is a registered trademark of NOAA.

The code offered in this repository is licensed under ["The MIT License"] [license] ("the License");
you may not use this software except in compliance with the License.

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and limitations under the License.

[website]: http://thepizzy.net/blog
[user_doco]: http://thepizzy.net/blog/labs/skywarn-storm-spotter-user-guide/
[neotsn_twitter]: https://twitter.com/neotsn
[license]: https://github.com/neotsn/spotter_status/blob/master/LICENSE
