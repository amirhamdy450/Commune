


document.addEventListener('DOMContentLoaded', () => {
	const NavMenuDropBtn=document.getElementById('NavMenuDropBtn');		
	const NavMenuDrop=document.getElementById('NavMenuDrop');
	
	NavMenuDropBtn.addEventListener("click", ()=>{
		
		NavMenuDrop.classList.toggle("hidden");
		
	});
    

});