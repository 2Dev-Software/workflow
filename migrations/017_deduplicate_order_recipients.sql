DELETE r1
FROM dh_order_recipients AS r1
INNER JOIN dh_order_recipients AS r2
    ON r1.orderID = r2.orderID
   AND r1.targetType = r2.targetType
   AND (r1.fID <=> r2.fID)
   AND (r1.roleID <=> r2.roleID)
   AND (r1.pID <=> r2.pID)
   AND r1.isCc = r2.isCc
   AND r1.recipientID > r2.recipientID;
