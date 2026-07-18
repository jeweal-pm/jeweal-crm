เพิ่มฟังก์ชั่นระบบ crm ประกอบด้วย

1  Role / Permission
    - root
    - ceo (permission เหมือนกับ root)
        - สามารถ assign ลูกค้าให้ sale,sale manager ได้
    - general manager
        - สามารถ assign ลูกค้าให้ sale,sale manager ได้
    - admin
    - sale manager
        - สามารถ assign ลูกค้า ให้ sale ได้
    - sale
        -  ดูหน้ารายการลูกค้าที่ ceo , manager , sale manager assign มาได้
        -  อัพเดทสถานะลูกค้าได้
        -  ไม่สามารถลบข้อมูลได้
    

2 Enquiry
    -  Enquiry การที่คนที่กรอก mail form มาทาง Website
    -  สถานะของ Enquiry แบ่งออกเป็น 3 แบบ ด้วยกันได้แก่
        - Lead / MQL ( ผู้ที่กรอกข้อมูลมาทาง form mail ของเว็บไซต์)
        - SQL ( Sales Qualified Lead )
        - propspect ( ผู้ให้ความสนใจสินค้าแล้ว และอาจมีการขอ trial version )
        - Customer ลูกค้าทดลองใช้สินค้าแล้วพอใจ จึงตกลงจ่ายเงินซื้อสินค้าจริง

3 การ assign
    -   เวลามีการ assign lead ให้ user ใด user นั้นจะได้รับ notification 
        - ผ่าน tab browser
        - ผ่าน email

    -   ต้องมีการเปิด socket เผื่อให้ในอนาคตมีการทำ mobile app  สามารถเข้ามาดึงข้อมูล realtime ได้ โดย ผู้ใช้ app จะต้อง login app ก่อนเท่านั้น

4 การ track update
    - สร้าง table ไว้ track การ update เบื้องต้น จะมี field lasted_updated_by,lasted_updated_by เพิ่อไว้บันทึกว่าใครเป็นผู้แก้ไข เพื่อตรวจสอบ kpi ว่า เวลาส่งงานมาที่แผนก sale มีการ action ไวแค่ไหน
    - ในหน้า track update จะมีแค่ CEO กับ general manager เท่านั้นที่เขาดูได้
    - กรณีที่ ceo หรือ general manager เป็นคนปิดเองจะต้องไม่ถูก นับเป็นการปิดของฝั่ง sale

5 หน้า ui /enquiry , /gis-enquiry  ใหม่จะประกอบด้วย
    - table สามารถ filter ข้อมูลได้ โดยต้องทำ api สำหรับ filter enquiry มาเพิ่ม
    - ให้สามารถลบ ข้อมูลได้แบบ soft_delete
    - เพิ่ม status ของ Enquiry ได้แก่ deleted
    - เพิ่ม column assign_to ให้แต่ละลิส เพื่อให้สามารถเลือกได้ว่าจะ assign ให้ใคร
    - เพิ่ม checkbox แบบ multi select สามารถเลือก ลบ ที่ละเหลายๆรายการได้