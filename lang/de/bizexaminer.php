<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_bizexaminer
 * @category    lang
 * @copyright   2023 bizExaminer <moodle@bizexaminer.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Required strings.
$string['pluginname'] = 'bizExaminer';
$string['modulename'] = 'bizExaminer Prüfung';
$string['modulename_help'] = 'Führen Sie eine bizExaminer Prüfung in ihrem Kurs durch.

Prüfungen werden unterschiedlich eingesetzt und haben sehr umfangreiche Einstelloptionen.

Jeder Versuch wird automatisch bewertet (oder manuell, abhängig davon wie die Fragen in bizExaminer konfiguriert sind), und die Endnote wird in den Bewertungen festgehalten.

Ein Exam Modul muss in bizExaminer konfiguriert werden, Remote Proctor Verbindungen von bizExaminer können ebenfalls verwendet werden.';
$string['modulenameplural'] = 'bizExaminer Prüfungen';
$string['pluginadministration'] = 'bizExaminer Verwaltung';

// Strings from acces.php (capabilities).
$string['bizexaminer:addinstance'] = 'Neue Prüfung erstellen';
$string['bizexaminer:view'] = 'Prüfung ansehen';
$string['bizexaminer:attempt'] = 'Prüfung durchführen';
$string['bizexaminer:viewownattempt'] = 'Eigene Antritte zu einer Prüfung ansehen';
$string['bizexaminer:viewanyattempt'] = 'Alle Antritte (von beliebigen Teilnehmer/innen) zu einer Prüfung ansehen';
$string['bizexaminer:deleteanyattempt'] = 'Antritt (eines beliebigen Teilnehmenden) löschen';

// Strings from index.php.

// General error messages.
$string['nocredentials'] = 'Bitte konfigurieren Sie die API Zugangsdaten.';

// Strings from settings.php (settings).
$string['apikeyowner'] = 'API Schlüssel Owner';
$string['apikeyowner_desc'] = 'Der API Schlüssel für den Owner.';

$string['apikeyorganisation'] = 'API Schlüssel Organisation';
$string['apikeyorganisation_desc'] = 'Der API Schlüssel für die Organisation.';

$string['apikeyinstance'] = 'Instanz Domain';
$string['apikeyinstance_desc'] = 'Der Domainname Ihrer bizExaminer Instanz (ohne https:// oder Pfad).';

$string['settings_apicredentials_actions_delete'] = 'Löschen';
$string['settings_apicredentials_actions_delete_disabled'] = 'API Zugangsdaten können nicht gelöscht werden, wenn sie noch in Prüfungen verwendet werden.';
$string['settings_apicredentials_actions_test_disabled'] = 'Bitte speicheren Sie Ihre Änderungen, bevor sie die Zugangsdaten testen.';


$string['configureapi'] = 'API Zugangsdaten konfigurieren';
$string['testapi'] = 'Zugangsdaten testen';
$string['checktestapi'] = 'Zugangsdaten testen';
$string['testapi_desc'] = 'Teste API Zugangsdaten.';
$string['testapi_error'] = 'Testen der API Zugangsdaten war fehlerhaft. Bitte überprüfen Sie die Zugangsdaten.';
$string['testapi_success'] = 'Testen der API Zugangsdaten war erfolgreich.';
$string['test_credential'] = 'Prüfen';

// Strings from module form.
$string['modform_exam_module'] = 'Exam Modul';
$string['modform_exam_module_help'] = 'Wählen Sie ein Exam Modul und eine Inhaltsversion aus.';
$string['exam_module_invalid'] = 'Bitte wählen Sie ein gültiges Exam Modul aus.';
$string['modform_exam_module_error'] = 'Fehler beim Laden der Exam Module. Überprüfen Sie bitte auch die API Zugangsdaten - Sie können diese in den Einstellungen testen.';
$string['modform_exam_module_none'] = 'Keine Exam Module gefunden. Bitte stellen Sie sicher, dass ein Exam in bizExaminer angelegt ist.
Überprüfen Sie bitte auch die API Zugangsdaten - Sie können diese in den Einstellungen testen.';
$string['modform_usebecertificate'] = 'bizExaminer Zertifikate verwenden.';
$string['modform_usebecertificate_help'] = 'Aktivieren Sie diese Option, um dem Nutzer das Zertifikat aus bizExaminer anzuzeigen.';
$string['modform_remote_proctor'] = 'Remote Proctoring verwenden';
$string['modform_remote_proctor_help'] = 'Wählen Sie einen konfigurierten Remote Proctoring Service aus.';
$string['modform_remote_proctor_invalid'] = 'Sie müssen einen Remote Proctoring Service oder "Kein Remote Proctoring" auswählen.';
$string['modform_remote_proctor_error'] = 'Fehler beim Laden der Remote Proctoring Accounts. Überprüfen Sie bitte die API Zugangsdaten - Sie können diese in den Einstellungen testen.';
$string['modform_remote_proctor_none'] = 'Keine Remote Proctoring Services gefunden. Bitte stellen Sie sicher, dass Remote Proctor Accounts angelegt sind.
Überprüfen Sie bitte auch die API Zugangsdaten - Sie können diese in den Einstellungen testen.';

$string['modform_attemptsallowed'] = 'Erlaubte Antritte';
$string['modform_grademethod'] = 'Bewertungsmethode';
$string['modform_grademethod_help'] = 'Wenn mehrere Antritte erlaubt sind, gibt es unterschiedliche Möglichkeiten, eine abschließende Bewertung für die Teilnehmer/innen festzulegen:

* Beste Bewertung aus allen Antritte
* Durchschnitt aus allen Antritte
* Erster Antritt (alle weiteren Antritte werden ignoriert)
* Letzter Antritt (alle weiteren Antritte werden ignoriert)';

$string['overallfeedback'] = 'Gesamtfeedback';
$string['overallfeedback_help'] = 'Das Gesamtfeedback ist der Text, welcher angezeigt wird, wenn eine Prüfung abgeschlossen wurde. Indem zusätzliche Mindestpunkte/-noten eingetragen werden, kann der Text je nach Ergebnis unterschiedlich lauten.';
$string['modform_feedbacktext'] = 'Feedback';
$string['modform_mingrade'] = 'Mindestpunkte/-note';
$string['modform_add_feedbacks'] = '{no} weitere Feedbackfelder hinzufügen';

// Proctor settings.
$string['modform_proctorexam_settings'] = 'Proctor Exam Settings';
$string['modform_proctorexam_sessionType'] = 'Session Type';
$string['modform_proctorexam_sessionType_classroom'] = 'Classroom';
$string['modform_proctorexam_sessionType_record_review'] = 'Record Review';
$string['modform_proctorexam_sessionType_live_proctoring'] = 'Live Proctoring';
$string['modform_proctorexam_mobileCam'] = 'Use mobile camera';
$string['modform_proctorexam_mobileCam_help'] = 'Use mobile camera as additional recording device';
$string['modform_proctorexam_dontSendEmails'] = 'Keine Emails an TeilnehmerInnen senden';
$string['modform_proctorexam_examInfo'] = 'Allgemeine Anweisungen';
$string['modform_proctorexam_examInfo_help'] = 'Diese werden angezeigt, bevor der Teilnehmer die Prüfung startet.';
$string['modform_proctorexam_individualInfo'] = 'Individuelle Informationen für TeilnehmerInnen.';
$string['modform_proctorexam_individualInfo_help'] = 'Ein personalisierter Link zum Starten der Prüfung (Text siehe unten) wird am Ende des Texts eingefügt. Alternativ kann mit <code>##start_exam##</code> die Position des Links genauer kontrolliert werden.';
$string['modform_proctorexam_startExamLinkText'] = 'Text für den »Prüfung starten« Link';
$string['modform_proctorexam_startExamLinkText_default'] = 'Starte Exam';

$string['modform_examity_settings'] = 'Examity Settings';
$string['modform_examity_courseId'] = 'ID of the course';
$string['modform_examity_courseName'] = 'Name of the course';
$string['modform_examity_instructorFirstName'] = 'First name of the instructor';
$string['modform_examity_instructorLastName'] = 'Last name of the instructor';
$string['modform_examity_instructorEmail'] = 'Email address of the instructor';
$string['modform_examity_examName'] = 'Name of the exam';
$string['modform_examity_examLevel'] = 'Session Type';
$string['modform_examity_examLevel_live_auth'] = 'Live Authentication';
$string['modform_examity_examLevel_auto_proctoring_premium'] = 'Automated Proctoring Premium';
$string['modform_examity_examLevel_record_review'] = 'Record and Review Proctoring';
$string['modform_examity_examLevel_live_proctoring'] = 'Live Proctoring';
$string['modform_examity_examLevel_auto_auth'] = 'Auto-Authentication';
$string['modform_examity_examLevel_auto_proctoring_standard'] = 'Automated Proctoring Standard';
$string['modform_examity_examInstructions'] = 'Instructions for the student';
$string['modform_examity_proctorInstructions'] = 'Instructions for the proctor';

$string['modform_examus_settings'] = 'Alemira Settings';
$string['modform_examus_language'] = 'Constructor Sprache';
$string['modform_examus_language_en'] = 'Englisch';
$string['modform_examus_language_ru'] = 'Russisch';
$string['modform_examus_language_es'] = 'Spanisch';
$string['modform_examus_language_it'] = 'Italienisch';
$string['modform_examus_language_ar'] = 'Arabisch';
$string['modform_examus_proctoring'] = 'Typ';
$string['modform_examus_identification'] = 'Identifikation';
$string['modform_examus_identification_face'] = 'Gesicht';
$string['modform_examus_identification_passport'] = 'Reisepass';
$string['modform_examus_identification_face_and_passport'] = 'Gesicht und Reisepass';
$string['modform_examus_respondus'] = 'Respondus LockDown Browser verwenden';
$string['modform_examus_respondus_help'] = 'Respondus LockDown Browser verwenden';

$string['modform_proctorio_settings'] = 'Proctorio';
$string['modform_proctorio_recordVideo'] = 'Video aufzeichnen';
$string['modform_proctorio_recordAudio'] = 'Audio aufzeichnen';
$string['modform_proctorio_recordScreen'] = 'Bildschirm aufzeichnen';
$string['modform_proctorio_recordRoomStart'] = 'Record room on start';
$string['modform_proctorio_recordRoomStart_help'] = 'Require the test taker to perform a room scan before starting the exam';
$string['modform_proctorio_verifyIdMode'] = 'Verify ID';
$string['modform_proctorio_verifyIdMode_no'] = 'no';
$string['modform_proctorio_verifyIdMode_auto'] = 'Automatic ID verification';
$string['modform_proctorio_verifyIdMode_live'] = 'Live ID verification';
$string['modform_proctorio_closeOpenTabs'] = 'Close open tabs';
$string['modform_proctorio_allowNewTabs'] = 'Allow new tabs';
$string['modform_proctorio_fullscreenMode'] = 'Force fullscreen';
$string['modform_proctorio_fullscreenMode_no'] = 'no';
$string['modform_proctorio_fullscreenMode_lenient'] = 'Lenient';
$string['modform_proctorio_fullscreenMode_moderate'] = 'Moderate';
$string['modform_proctorio_fullscreenMode_severe'] = 'Severe';
$string['modform_proctorio_disableClipboard'] = 'Disable clipboard';
$string['modform_proctorio_disableRightClick'] = 'Disable rightclick';
$string['modform_proctorio_disableDownloads'] = 'Disable downloads';
$string['modform_proctorio_disablePrinting'] = 'Disable printing';

// Modform: Access restrictions.
$string['modform_access_restrictions'] = 'Weitere Zugriffsbeschränkungen';
$string['modform_access_restrictions_password'] = 'Kennwort';
$string['modform_access_restrictions_password_help'] = 'Wenn Sie ein Kennwort festlegen, müssen die Teilnehmer/innen zuerst das Kennwort eingeben, bevor sie die Prüfung starten können. Muss 4-12 Zeichen lang sein.';
$string['modform_access_restrictions_password_error_length'] = 'Das Kennwort muss 4-12 Zeichen lang sein.';
$string['modform_access_restrictions_requiresubnet'] = 'IP-Adresse';
$string['modform_access_restrictions_requiresubnet_help'] = 'Sie können den Zugriff auf bestimmte Rechner oder IP-Adressen beschränken, wenn z.B. nur Teilnehmer/innen die Prüfung in einem bestimmten Raum durchführen dürfen. Die zugelassenen IP-Adressen geben Sie in einer kommagetrennten Liste teilweise oder vollständig an (z.B. <b>192.168. , 231.54.211.0/20, 231.3.56.211</b>).';
$string['modform_access_restrictions_delay1st2nd'] = 'Pause zwischen 1. und 2. Antritt';
$string['modform_access_restrictions_delay1st2nd_help'] = 'Wenn diese Option aktiviert ist, können Studierende einen zweiten Antritt erst nach Ablauf der festgelegten Zeit durchführen.';
$string['modform_access_restrictions_delaylater'] = 'Pause bei späteren Antritten';
$string['modform_access_restrictions_delaylater_help'] = 'Wenn diese Option aktiviert ist, können Studierende einen dritten und weitere Antritte erst nach Ablauf der festgelegten Zeit durchführen.';
$string['modform_access_restrictions_timeopen'] = 'Prüfung öffnen ab';
$string['modform_access_restrictions_timeopen_help'] = 'Teilnehmer/innen dürfen ihre Antritte nach dem Beginn anfangen und müssen sie vor den Ende beendet haben.';
$string['modform_access_restrictions_timeclose'] = 'Prüfung schließen ab';
$string['modform_access_restrictions_timeclose_error_beforopen'] = 'Sie haben einen Endzeitpunkt vor dem Startzeitpunkt angegeben.';
$string['modform_access_restrictions_overduehandling'] = 'Wenn die Zeit abgelaufen ist';
$string['modform_access_restrictions_overduehandling_help'] = 'Was soll geschehen, wenn Teilnehmer/innen einen Antritt nicht abschließen bevor der Zeitraum abgelaufen ist';
$string['modform_access_restrictions_overduehandling_graceperiod'] = 'Es gibt eine Nachfirst, in der laufende Antritte abgegeben werden können.';
$string['modform_access_restrictions_overduehandling_autoabandon'] = 'Der Antritt muss abgegeben werden, bevor die Zeit abgelaufen ist, damit er gewertet werden kann.';
$string['modform_access_restrictions_overduehandling_graceperiod_field'] = 'Nachfrist für Abgabe';
$string['modform_access_restrictions_overduehandling_graceperiod_field_help'] = 'Wenn für den Zeitablauf gewählt wurde "Es gibt eine Nachfirst, in der laufende Antritte abgegeben werden können" wird diese zusätzliche Zeitdauer gewährt.';

// Reset form.
$string['resetform_remove_attempts'] = 'Alle Antritte löschen';
$string['reset_delete_attempts'] = 'Antritte gelöscht';
$string['reset_delete_grades'] = 'Gespeicherte Bewertungen gelöscht';
$string['reset_grades'] = 'Gradebook Bewertungen gelöscht';

// Exam view.
$string['exam_startattempt'] = 'Starte Prüfung';
$string['exam_retakeattempt'] = 'Prüfung erneut versuchen';
$string['exam_resumeattempt'] = 'Prüfung fortsetzen';
$string['exam_pendingresults_you'] = 'Sie haben die Prüfung noch nicht fertig abgeschlossen oder die Ergebnisse werden noch manuell überprüft.
Sie finden die Ergebnisse in Ihrem Profil, sobald sie fertig sind.';
$string['exam_pendingresults_user'] = 'Der/die Teilnehmer/in hat die Prüfung noch nicht fertig abgeschlossen oder die Ergebnisse werden noch manuell überprüft.
Sie finden die Ergebnisse hier, sobald sie fertig sind.';
$string['exam_error_participant'] = 'Der/die Teilnehmer/in konnte nicht via API erstellt werden.';
$string['exam_error_save_attempt'] = 'Der Antritt konnte nicht gespeichert werden.';
$string['exam_error_save_results'] = 'Die Ergebnisse konnten nicht gespeichert werden.';
$string['exam_error_booking'] = 'Es konnte keine Buchung/Schedule via API erstellt werden.';

$string['exam_access_timeopen'] = 'Die Prüfung ist verfügbar ab {$a}';
$string['exam_access_timeclose'] = 'Diese Prüfung ist momentan nicht verfügbar.';
$string['exam_access_timeclosed'] = 'Die Prüfung ist bereits geschlossen.';
$string['exam_access_subnetwrong'] = 'Diese Prüfung kann nur von bestimmten festgelegten Computern aus durchgeführt werden. Ihr Computer befindet sich nicht auf der Liste.';
$string['exam_access_nomoreattempts'] = 'Es sind keine weiteren Antritte erlaubt.';
$string['exam_access_wait'] = 'Sie müssen abwarten, bevor Sie eine Wiederholung für diese Prüfung versuchen dürfen. Sie dürfen einen weiteren Antritt nach {$a} beginnen.';

$string['exam_view_certificate'] = 'Zertifikat ansehen';

$string['attempts_table_heading_yours'] = 'Zusammenfassung der vorherigen Antritte';
$string['attempts_table_heading_all'] = 'Zusammenfassung aller Antritte';

$string['deletattempt'] = 'Löschen';
$string['deleteattemptcheck'] = 'Möchten Sie diesen Antritt wirklich löschen?';
$string['deletedattempt'] = 'Antritt wurde erfolgreich gelöscht.';

$string['attempts_table_user'] = 'Nutzer/in';
$string['attempts_table_no'] = 'Antritt #';
$string['attempts_table_actions'] = 'Aktionen';
$string['attempt_viewattempt'] = 'Details';
$string['attempt_pass'] = 'Bestanden';
$string['attempt_failed'] = 'Nicht bestanden';
$string['attempt_noresults'] = '-';

$string['attempt_status_started'] = 'Gestartet';
$string['attempt_status_pendingresults'] = 'Ergebnisse ausstehend';
$string['attempt_status_completed'] = 'Abgeschlossen';
$string['attempt_status_aborted'] = 'Abgebrochen';
$string['attempt_status_date_started'] = 'Begonnen am {$a}';
$string['attempt_status_date_completed'] = 'Beendet am {$a}';

// Attempt view.
$string['attempt_heading'] = 'Antritt für {$a}';
$string['attempts'] = 'Antritte';
$string['attempts_heading'] = 'Antritte für {$a}';
$string['attempts_no'] = '{$a} Antritte';
$string['attempts_view_all'] = 'Alle Antritte ansehen';

// Grading.
$string['grade_infos'] = 'Bewertung';
$string['grade_current'] = 'Ihre Bewertung';
$string['gradehighest'] = 'Beste Bewertung';
$string['gradeaverage'] = 'Durchschnitt';
$string['gradeattemptfirst'] = 'Erster Antritt';
$string['gradeattemptlast'] = 'Letzter Antritt';
$string['grade_pass_out_of'] = '{$a->gradepass} von {$a->maxgrade}';

// Results.
$string['results_notification_passed'] = 'Sie haben die Prüfung bestanden.';
$string['results_notification_not_passed'] = 'Sie haben die Prüfung nicht bestanden.';
$string['results_whenstarted'] = 'Begonnen am';
$string['results_pass'] = 'Ergebnis';
$string['results_state'] = 'Status';
$string['results_user'] = 'Nutzer/in';
$string['results_whenfinished'] = 'Beendet am';
$string['results_timetaken'] = 'Verbrauchte Zeit';
$string['results_score'] = 'Punkte';
$string['results_questionscount'] = 'Fragen';
$string['results_questionscorrectcount'] = 'Korrekte Fragen';
$string['results_grade_link'] = '<a href="{$a}">Siehe Bewertungen</a>';

// Callback API.
$string['callbackapi_differentuser'] = 'Sie sind nicht als der/die Nutzer/in eingeloggt, der/die Prüfung gestartet hat.';

// Tasks.
$string['task_cleanup_abandoned'] = 'Aufgegebene Antritte abrechen.';

// General errors.
$string['error_saving_exam'] = 'Beim Speichern der Prüfung ist ein Fehler aufgetreten.';
$string['error_general'] = 'Etwas ist schief gelaufen. Bitte probieren Sie es noch einmal oder kontaktieren Sie den Administrator.';

// Privacy.
$string['privacy:metadata:attempts'] = 'Details zu jedem Antritt der Prüfung';
$string['privacy:metadata:attempts:examid'] = 'Die Prüfung zu der angetreten wurde.';
$string['privacy:metadata:attempts:userid'] = 'Antretende/r Teilnehmer/in.';
$string['privacy:metadata:attempts:status'] = 'Derzeitiger Status des Antritts.';
$string['privacy:metadata:attempts:bookingid'] = 'Die bookingId in bizExaminer (siehe Externe Daten).';
$string['privacy:metadata:attempts:participantid'] = 'Die participantid in bizExaminer (siehe Externe Daten).';
$string['privacy:metadata:attempts:timecreated'] = 'Zeitpunkt zu dem Versuch erstellt und gestartet wurde.';
$string['privacy:metadata:attempts:timemodified'] = 'Zeitpunkt zu dem Versuch aktualisiert wurde.';
$string['privacy:metadata:attempts:attempt'] = 'Antrittsnummer';
$string['privacy:metadata:attempts:validto'] = 'Zeitpunkt bis wann der Antritt in bizExaminer gültig ist.';

$string['privacy:metadata:attempt_results'] = 'Detaillierte Ergebnisse für Antritte aus bizExaminer.';
$string['privacy:metadata:attempt_results:userid'] = 'Antretende/r Teilnehmer/in.';
$string['privacy:metadata:attempt_results:attemptid'] = 'Die ID des Antritts.';
$string['privacy:metadata:attempt_results:whenfinished'] = 'Zeitpunkt zu dem Versuch beendet wurde.';
$string['privacy:metadata:attempt_results:timetaken'] = 'Benötigte Zeit für den Antritt.';
$string['privacy:metadata:attempt_results:result'] = 'Prozentpunkte die der/die Teilnehmer/in erreicht hat.';
$string['privacy:metadata:attempt_results:pass'] = 'Ob die Prüfung erfolgreich abgelegt wurde.';
$string['privacy:metadata:attempt_results:achievedscore'] = 'Erreichte Punkteanzahl';
$string['privacy:metadata:attempt_results:maxscore'] = 'Maximal mögliche Punkteanzahl';
$string['privacy:metadata:attempt_results:questionscount'] = 'Anzahl angezeigter Fragen';
$string['privacy:metadata:attempt_results:questionscorrectcount'] = 'Anzahl (korrekt) beantworteter Fragen.';
$string['privacy:metadata:attempt_results:certificateurl'] = 'URL zum bizExaminer Zertifikat';

$string['privacy:metadata:grades'] = 'Details über die Gesamtbewertung der Prüüfung.';
$string['privacy:metadata:grades:examid'] = 'Die Prüfung die bewertet wurde.';
$string['privacy:metadata:grades:userid'] = 'Bewertete/r Teilnehmer/in';
$string['privacy:metadata:grades:grade'] = 'Gesamtbewertung der Prüfung';
$string['privacy:metadata:grades:timemodified'] = 'Zeitpunkt an dem Bewertung geändert wurde';
$string['privacy:metadata:grades:timesubmitted'] = 'Zeitpunkt an dem Bewertung erstellt wurde (=Antritt wurde abgegeben).';

$string['privacy:metadata:bizexaminer'] = 'Daten die zu bizExaminer gesendet werden.';
$string['privacy:metadata:bizexaminer:firstname'] = 'Vorname des/der Teilnehmer/in aus dem Moodle Profil.';
$string['privacy:metadata:bizexaminer:lastname'] = 'Nachname des/der Teilnehmer/in aus dem Moodle Profil.';
$string['privacy:metadata:bizexaminer:email'] = 'E-Mail Adresse des/der Teilnehmer/in aus dem Moodle Profil.';
