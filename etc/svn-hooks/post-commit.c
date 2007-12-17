#define REAL_SCRIPT "/home/misterhaan/svn/track7/hooks/post-commit.bash"
#include <sys/types.h>
#include <unistd.h>
main( ac, av )
	  char **av;
{
	  execv( REAL_SCRIPT, av );
}
