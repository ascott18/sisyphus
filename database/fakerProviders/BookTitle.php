<?php

namespace Faker\Provider;

class BookTitle extends \Faker\Provider\Base
{
    protected static $titleList = array(

        "HTML and CSS: Design and Build Websites",
        "Don't Make Me Think, Revisited: A Common Sense Approach to Web Usability (3rd Edition) (Voices That Matter)",
        "JavaScript and JQuery: Interactive Front-End Web Development",
        "Web Design with HTML, CSS, JavaScript and jQuery Set",
        "Learning Web Design: A Beginner's Guide to HTML, CSS, JavaScript, and Web Graphics",
        "Programming Interviews Exposed: Secrets to Landing Your Next Job",
        "About Face: The Essentials of Interaction Design",
        "Beginning Programming with Java For Dummies",
        "CSS Secrets: Better Solutions to Everyday Web Design Problems",
        "Coding For Dummies (For Dummies (Computers))",
        "Rocket Surgery Made Easy: The Do-It-Yourself Guide to Finding and Fixing Usability Problems",
        "Professional AngularJS",
        "Professional JavaScript for Web Developers",
        "The Elements of User Experience: User-Centered Design for the Web and Beyond (2nd Edition) (Voices That Matter)",
        "Head First HTML and CSS",
        "Adobe Creative Cloud Design Tools All-in-One For Dummies",
        "JavaScript for Kids: A Playful Introduction to Programming",
        "JavaScript & jQuery: The Missing Manual (Missing Manuals)",
        "The UX Book: Process and Guidelines for Ensuring a Quality User Experience",
        "Badass: Making Users Awesome",
        "Black Hat Python: Python Programming for Hackers and Pentesters",
        "Penetration Testing: A Hands-On Introduction to Hacking",
        "Metasploit: The Penetration Tester's Guide",
        "Practical Malware Analysis: The Hands-On Guide to Dissecting Malicious Software",
        "The Practice of Network Security Monitoring: Understanding Incident Detection and Response",
        "Hackers: Heroes of the Computer Revolution - 25th Anniversary Edition",
        "Hacking Healthcare: A Guide to Standards, Workflows, and Meaningful Use -",
        "Crafting the InfoSec Playbook: Security Monitoring and Incident Response Master Plan",
        "Network Security Through Data Analysis: Building Situational Awareness",
        "The Tangled Web: A Guide to Securing Modern Web Applications",
        "Gray Hat Python: Python Programming for Hackers and Reverse Engineers",
        "Hackers & Painters: Big Ideas from the Computer Age",
        "Android Security Internals: An In-Depth Guide to Android's Security Architecture",
        "A Bug Hunter's Diary: A Guided Tour Through the Wilds of Software Security",
        "Cloud Security and Privacy: An Enterprise Perspective on Risks and Compliance (Theory in Practice)",
        "Hacking and Securing iOS Applications: Stealing Data, Hijacking Software, and How to Prevent It",
        "The Myths of Security: What the Computer Security Industry Doesn't Want You to Know",
        "SOA Security",
        "Metal Gear Solid V: The Phantom Pain: The Complete Official Guide Collector's Edition",
        "A Work in Progress: A Memoir",
        "Tales of Zestiria Collector's Edition Strategy Guide",
        "The Future X Network: A Bell Labs Perspective",
    );

    /**
     * @example 'SOA Security'
     * @return string
     */
    public static function bookTitle()
    {
        return static::randomElement(static::$titleList);
    }
}
