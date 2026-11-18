<?php

namespace Database\Seeders;

use App\Models\CaseFile;
use App\Models\EvidenceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with CaseFile 404 records.
     */
    public function run(): void
    {
        // Investigator #118 (attendee/default user)
        $investigator118 = User::firstOrCreate(
            ['email' => 'investigator@casefile404.unit'],
            [
                'name' => 'Investigator #118',
                'password' => Hash::make('casefile404'),
            ]
        );

        // Investigator #204 (Internal Affairs)
        $investigator204 = User::firstOrCreate(
            ['email' => 'internal-affairs@casefile404.unit'],
            [
                'name' => 'Investigator #204',
                'password' => Hash::make('supersecretia204'),
            ]
        );

        // Administrator account
        $admin = User::firstOrCreate(
            ['email' => 'admin@casefile404.unit'],
            [
                'name' => 'Bureau Director',
                'password' => Hash::make('adminroot404'),
            ]
        );

        // Case #1042 (Belongs to Investigator #118)
        $case1042 = CaseFile::firstOrCreate(
            ['case_number' => '1042'],
            [
                'tag' => 'YOUR CASE',
                'title' => 'Server Room Intrusion',
                'owner' => 'Investigator #118 (you)',
                'owner_id' => $investigator118->id,
                'status' => 'Active',
                'notes' => 'Server logs show a badge login at 03:12, no matching entry log.',
                'evidence' => '4 files attached',
                'restricted' => false,
            ]
        );

        // Case #1043 (Restricted / Internal Affairs - targets IDOR)
        $case1043 = CaseFile::firstOrCreate(
            ['case_number' => '1043'],
            [
                'tag' => 'INTERNAL AFFAIRS',
                'title' => 'Internal Affairs Review',
                'owner' => 'Investigator #204',
                'owner_id' => $investigator204->id,
                'status' => 'Restricted',
                'notes' => 'Review of conduct during Case #1038. Contains confidential officer evaluations and sealed logs. Not for general access.',
                'evidence' => '9 files attached',
                'restricted' => true,
            ]
        );

        // Case #404 (Core breach case file)
        $case404 = CaseFile::firstOrCreate(
            ['case_number' => '404'],
            [
                'tag' => 'CRITICAL BREACH',
                'title' => 'The Missing Evidence',
                'owner' => 'Special Incident Taskforce',
                'owner_id' => $admin->id,
                'status' => 'Under Investigation',
                'notes' => 'Chain-of-custody archive compromised. Unauthorized DB traversal & internal SSRF probes detected.',
                'evidence' => '17 files attached',
                'restricted' => true,
            ]
        );

        // Evidence items
        EvidenceItem::firstOrCreate(
            ['evidence_id' => 'EV-1042-01'],
            [
                'case_file_id' => $case1042->id,
                'file_name' => 'badge_reader_0312.pcap',
                'file_type' => 'pcap',
                'sha256_hash' => '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08',
                'uploaded_by' => 'Investigator #118',
                'classification' => 'CONFIDENTIAL',
            ]
        );

        EvidenceItem::firstOrCreate(
            ['evidence_id' => 'EV-1043-IA'],
            [
                'case_file_id' => $case1043->id,
                'file_name' => 'officer_evaluations_confidential.pdf',
                'file_type' => 'document',
                'sha256_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'uploaded_by' => 'Investigator #204',
                'classification' => 'TLP:RED',
            ]
        );

        EvidenceItem::firstOrCreate(
            ['evidence_id' => 'EV-404-METADATA'],
            [
                'case_file_id' => $case404->id,
                'file_name' => 'internal_cloud_metadata_leak.json',
                'file_type' => 'log',
                'sha256_hash' => '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8',
                'uploaded_by' => 'Forensic Unit',
                'classification' => 'RESTRICTED',
            ]
        );
    }
}
