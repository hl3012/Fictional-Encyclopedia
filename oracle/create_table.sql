DROP TABLE MAKESEDITREQUEST CASCADE CONSTRAINTS;
DROP TABLE CONTRIBUTESTO CASCADE CONSTRAINTS;
DROP TABLE COMMENTS CASCADE CONSTRAINTS;
DROP TABLE ISIN CASCADE CONSTRAINTS;
DROP TABLE LINKTO CASCADE CONSTRAINTS;
DROP TABLE CATEGORIESAMOUNT CASCADE CONSTRAINTS;

DROP TABLE VERIFIEDUSERS CASCADE CONSTRAINTS;
DROP TABLE ADMIN CASCADE CONSTRAINTS;

DROP TABLE USERS CASCADE CONSTRAINTS;
DROP TABLE ENTRY CASCADE CONSTRAINTS;
DROP TABLE CATEGORIES CASCADE CONSTRAINTS;





-- 	Entry-IsIn-Category
CREATE TABLE Entry (
    eid INTEGER PRIMARY KEY,
    name VARCHAR2(100) UNIQUE NOT NULL,
    content VARCHAR2(2000) NOT NULL,
    image_url VARCHAR2(300)
);

CREATE TABLE Categories (
    caID INTEGER PRIMARY KEY,
    name VARCHAR2(100) UNIQUE NOT NULL,
    content VARCHAR2(2000) NOT NULL
);

CREATE TABLE CategoriesAmount (
    caID INTEGER PRIMARY KEY,
    entries_count NUMBER NOT NULL,
    FOREIGN KEY(caID) REFERENCES Categories(caID) ON DELETE CASCADE
);

CREATE TABLE IsIn (
    caID INTEGER,
    eid INTEGER,
    PRIMARY KEY(caID, eid),
    FOREIGN KEY (eid) REFERENCES Entry(eid) ON DELETE CASCADE,
    FOREIGN KEY (caID) REFERENCES Categories(caID) ON DELETE CASCADE
);


CREATE TABLE Users (
    userId INTEGER PRIMARY KEY,
    name VARCHAR2(100) NOT NULL,
    password VARCHAR2(100) NOT NULL
);


CREATE TABLE Comments (
    coID INTEGER PRIMARY KEY,  
    eid INTEGER NOT NULL,
    userId INTEGER NOT NULL,
    content VARCHAR2(2000) NOT NULL,
    commentdate DATE NOT NULL,
    replyTo INTEGER NULL,
    FOREIGN KEY (replyTo) REFERENCES Comments(coID) ON DELETE CASCADE,
    FOREIGN KEY (eid) REFERENCES Entry(eid) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE
);

-- ISA
CREATE TABLE VerifiedUsers (
    userId INTEGER PRIMARY KEY,
    FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE
);

CREATE TABLE Admin (
    userId INTEGER PRIMARY KEY,
    FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE
);

-- Enrty LinkTo
CREATE TABLE LinkTo (
    eid1 INTEGER,
    eid2 INTEGER,
    PRIMARY KEY(eid1, eid2),
    FOREIGN KEY (eid1) REFERENCES Entry(eid) ON DELETE CASCADE,
    FOREIGN KEY (eid2) REFERENCES Entry(eid) ON DELETE CASCADE
);


-- User request edit per entryid then admin approve
CREATE TABLE makesEditRequest (
    eid INTEGER,
    userId INTEGER,
    adminId INTEGER,
    rid INTEGER,
    requestDate DATE,
    PRIMARY KEY(eid,userId,adminId,rid),
    FOREIGN KEY (eid) REFERENCES Entry(eid) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES Users(userId) ON DELETE CASCADE,
    FOREIGN KEY (adminId) REFERENCES Admin(userId) 
);

-- 	Entry-User-Contribution
CREATE TABLE ContributesTo (
    userId INTEGER,
    eid INTEGER,
    conDate DATE NOT NULL,
    PRIMARY KEY(userId, eid),
    FOREIGN KEY (userId) REFERENCES Users(userId),
    FOREIGN KEY (eid) REFERENCES Entry(eid) ON DELETE CASCADE
);


-- add indexing and triggers
CREATE INDEX idx_entry_name ON Entry(name);          
CREATE INDEX idx_entry_content ON Entry(content);       
CREATE INDEX idx_comments_eid ON Comments(eid);      
CREATE INDEX idx_isin_caid ON IsIn(caID);            

CREATE OR REPLACE PROCEDURE GetUserBasicStats(
    p_userId IN INTEGER,
    p_entryCount OUT INTEGER
) AS
BEGIN
   
    SELECT COUNT(*) INTO p_entryCount 
    FROM ContributesTo 
    WHERE userId = p_userId;
END;
/

CREATE OR REPLACE TRIGGER LogEntryChange
AFTER INSERT OR UPDATE ON Entry
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        DBMS_OUTPUT.PUT_LINE('add new entry: ' || :NEW.name);
    ELSIF UPDATING THEN
        DBMS_OUTPUT.PUT_LINE('update entry: ' || :OLD.name || ' -> ' || :NEW.name);
    END IF;
END;
/

INSERT INTO Categories (caID, name, content) VALUES (1, 'Mobility', 'Movement-based powers');
INSERT INTO Categories (caID, name, content) VALUES (2, 'Offense', 'Offensive or elemental powers');
INSERT INTO Categories (caID, name, content) VALUES (3, 'Support', 'Utility or enhancement abilities');
INSERT INTO Categories (caID, name, content) VALUES (4, 'Control', 'Mind or influence-based powers');
INSERT INTO Categories (caID, name, content) VALUES (5, 'Defense', 'Protective and resilience powers');

INSERT INTO Entry (eid, name, content, image_url) VALUES (100, 'Time Freeze', 'Level: A, Halts time within 20m', '100.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (101, 'Teleportation', 'Level: B, Instant movement', '101.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (102, 'Pyrokinesis', 'Level: C, Fire manipulation', '102.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (103, 'Invisibility', 'Level: B, Vanish from sight', '103.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (104, 'Mind Control', 'Level: S, Override will', '104.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (105, 'Telepathy', 'Level: A, Read and send thoughts', '105.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (106, 'Force Field', 'Level: B, Create defensive barriers', '106.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (107, 'Cryokinesis', 'Level: B, Control over ice', '107.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (108, 'Illusion Casting', 'Level: B, Create visual illusions', '108.png');
INSERT INTO Entry (eid, name, content, image_url) VALUES (109, 'Mind Shield', 'Level: A, Block telepathic intrusions', '109.png');

INSERT INTO CategoriesAmount (caID, entries_count) VALUES (1, 1);
INSERT INTO CategoriesAmount (caID, entries_count) VALUES (2, 4);
INSERT INTO CategoriesAmount (caID, entries_count) VALUES (3, 2);
INSERT INTO CategoriesAmount (caID, entries_count) VALUES (4, 2);
INSERT INTO CategoriesAmount (caID, entries_count) VALUES (5, 1);

INSERT INTO IsIn (caID, eid) VALUES (4, 100);
INSERT INTO IsIn (caID, eid) VALUES (1, 101);
INSERT INTO IsIn (caID, eid) VALUES (2, 102);
INSERT INTO IsIn (caID, eid) VALUES (3, 103);
INSERT INTO IsIn (caID, eid) VALUES (4, 104);
INSERT INTO IsIn (caID, eid) VALUES (3, 105);
INSERT INTO IsIn (caID, eid) VALUES (2, 106);
INSERT INTO IsIn (caID, eid) VALUES (2, 107);
INSERT INTO IsIn (caID, eid) VALUES (2, 108);
INSERT INTO IsIn (caID, eid) VALUES (5, 109);

INSERT INTO Users (userId, name, password) VALUES (1, 'Monkey D. Luffy', 'luffy123');
INSERT INTO Users (userId, name, password) VALUES (2, 'Jotaro "JoJo" Kujo', 'jojo123');
INSERT INTO Users (userId, name, password) VALUES (3, 'Nagi seishiro', 'nagi123');
INSERT INTO Users (userId, name, password) VALUES (4, 'Anthony Edward Stark', 'stark123');
INSERT INTO Users (userId, name, password) VALUES (5, '24kChunshuai', 'chunshuai123');


INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(1, 100, 1, 'Very detailed power explanation.', TO_DATE('2025-07-10', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(2, 100, 2, 'This is way too strong! Nerf it.', TO_DATE('2025-07-11', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(3, 100, 3, 'Can someone add a weakness?', TO_DATE('2025-07-12', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(4, 100, 4, 'Love the lore behind this power.', TO_DATE('2025-07-13', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(5, 100, 5, 'Needs more citations.', TO_DATE('2025-07-14', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(6, 101, 2, 'This is way too strong! Nerf it.', TO_DATE('2025-07-11', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(7, 102, 3, 'Can someone add a weakness?', TO_DATE('2025-07-12', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(8, 103, 4, 'Love the lore behind this power.', TO_DATE('2025-07-13', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(9, 104, 5, 'Needs more citations.', TO_DATE('2025-07-14', 'YYYY-MM-DD'), NULL);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(10, 104, 5, 'I agree.', TO_DATE('2025-07-15', 'YYYY-MM-DD'), 9);
INSERT INTO Comments (coID, eid, userId, content, commentdate, replyTo) VALUES
(11, 105, 1, 'Interesting.', TO_DATE('2025-07-11', 'YYYY-MM-DD'), NULL);

--ISA
INSERT INTO VerifiedUsers (userId) VALUES (1);
INSERT INTO VerifiedUsers (userId) VALUES (2);
INSERT INTO VerifiedUsers (userId) VALUES (3);
INSERT INTO VerifiedUsers (userId) VALUES (4);
INSERT INTO VerifiedUsers (userId) VALUES (5);
INSERT INTO Admin (userId) VALUES (1);

--LinkTo
INSERT INTO LinkTo (eid1, eid2) VALUES (104, 105);
INSERT INTO LinkTo (eid1, eid2) VALUES (105, 109);
INSERT INTO LinkTo (eid1, eid2) VALUES (101, 100);

--EditRequest
INSERT INTO makesEditRequest (eid,userId,adminId,rid, requestDate) VALUES (100, 1, 1, 1, TO_DATE('2025-07-01', 'YYYY-MM-DD'));
INSERT INTO makesEditRequest (eid,userId,adminId,rid, requestDate) VALUES (101, 2, 1, 2, TO_DATE('2025-07-02', 'YYYY-MM-DD'));
INSERT INTO makesEditRequest (eid,userId,adminId,rid, requestDate) VALUES (102, 3, 1, 3, TO_DATE('2025-07-03', 'YYYY-MM-DD'));
INSERT INTO makesEditRequest (eid,userId,adminId,rid, requestDate) VALUES (103, 4, 1, 4, TO_DATE('2025-07-04', 'YYYY-MM-DD'));
INSERT INTO makesEditRequest (eid,userId,adminId,rid, requestDate) VALUES (104, 5, 1, 5, TO_DATE('2025-07-05', 'YYYY-MM-DD'));

--contributeTo
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (1, 100,  TO_DATE('2025-07-01', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (2, 101,  TO_DATE('2025-07-02', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (3, 102,  TO_DATE('2025-07-03', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (4, 103,  TO_DATE('2025-07-04', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (5, 104,  TO_DATE('2025-07-05', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (1, 105,  TO_DATE('2025-07-01', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (2, 106,  TO_DATE('2025-07-02', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (3, 107,  TO_DATE('2025-07-03', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (4, 108,  TO_DATE('2025-07-04', 'YYYY-MM-DD'));
INSERT INTO ContributesTo (userId, eid, conDate) VALUES (5, 109,  TO_DATE('2025-07-05', 'YYYY-MM-DD'));


COMMIT;