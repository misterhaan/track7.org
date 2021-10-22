delimiter ;;
create function GetConversationID(user1 smallint unsigned, user2 smallint unsigned)
returns smallint unsigned
deterministic
modifies sql data
begin
	if user2 is null
	then
		set user2 = 0;
	end if;
	if(select not exists(select id from users_conversations where thisuser=user1 and thatuser=user2))
	then
		insert into users_conversations (thisuser, thatuser) values (user1, user2);
		if user2 > 0 and user1 != user2
		then
			insert into users_conversations (id, thisuser, thatuser) values (last_insert_id(), user2, user1);
		end if;
	end if;
	return (select id from users_conversations where thisuser=user1 and thatuser=user2);
end;;
delimiter ;
